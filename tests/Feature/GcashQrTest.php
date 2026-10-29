<?php

namespace Tests\Feature;

use App\Models\ElectricBill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GcashQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_upload_replace_and_remove_their_receiving_qr(): void
    {
        Storage::fake('public');
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->put('/manager/payment-settings/gcash', [
            'gcash_qr' => UploadedFile::fake()->image('qr.png', 400, 400),
            'gcash_account_name' => 'My GCash', 'gcash_account_number' => '09171234567',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'gcash-updated');
        $path = $manager->fresh()->gcash_qr_path;
        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.png', $path);
        $this->get('/profile')->assertOk()->assertSee('My GCash')->assertSee('Save GCash details');
        $this->put('/manager/payment-settings/gcash', ['gcash_qr' => UploadedFile::fake()->image('new.jpg', 300, 300)])->assertSessionHas('status', 'gcash-updated');
        Storage::disk('public')->assertMissing($path);
        $replacement = $manager->fresh()->gcash_qr_path;
        $this->put('/manager/payment-settings/gcash', ['remove_qr' => 1])->assertSessionHas('status', 'gcash-updated');
        $this->assertNull($manager->fresh()->gcash_qr_path);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_only_managers_can_update_gcash_settings_and_non_images_are_rejected(): void
    {
        foreach (['tenant', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->put('/manager/payment-settings/gcash', ['gcash_account_name' => 'Not allowed'])->assertForbidden();
        }
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->put('/manager/payment-settings/gcash', [
            'gcash_qr' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('gcash_qr');
        $this->assertNull($manager->fresh()->gcash_qr_path);
    }

    public function test_rent_electricity_and_signup_show_the_assigned_manager_qr(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $manager->forceFill(['gcash_qr_path' => 'gcash-qr/manager.png', 'gcash_account_name' => 'Receiving Account'])->save();
        $other = User::factory()->create(['role' => 'manager']);
        $other->forceFill(['gcash_qr_path' => 'gcash-qr/other.png'])->save();
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'A', 'price' => 3000, 'status' => 'available']);
        Payment::create(['tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id, 'amount' => 3000, 'due_date' => today(), 'status' => 'pending']);
        ElectricBill::create([
            'tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id,
            'period_from' => today()->subMonth(), 'period_to' => today(), 'billing_date' => today(),
            'reading_previous' => 0, 'reading_present' => 10, 'kwh_used' => 10, 'rate_per_kwh' => 10,
            'total_amount' => 100, 'due_date' => today(), 'status' => 'pending',
        ]);
        foreach (['/tenant/bills', '/tenant/electric-bills'] as $url) {
            $this->actingAs($tenant)->get($url)->assertOk()->assertSee('gcash-qr/manager.png')->assertSee('Receiving Account')->assertDontSee('gcash-qr/other.png');
        }
        $application = RentalApplication::create([
            'stall_id' => $stall->id, 'name' => 'Applicant', 'email' => 'applicant@gmail.com', 'phone' => '09171234567',
            'address' => 'Main Street', 'password_hash' => bcrypt('password'), 'rental_duration' => 1, 'payment_status' => 'pending',
        ]);
        $this->get($application->signedUrl('rental.checkout'))->assertOk()->assertSee('gcash-qr/manager.png')->assertDontSee('gcash-qr/other.png');
    }

    public function test_supply_payments_show_active_manager_qrs_and_missing_qr_has_clear_instructions(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $manager->forceFill(['gcash_qr_path' => 'gcash-qr/supply.png'])->save();
        $inactive = User::factory()->create(['role' => 'manager', 'is_active' => false]);
        $inactive->forceFill(['gcash_qr_path' => 'gcash-qr/inactive.png'])->save();
        $tenant = User::factory()->create(['role' => 'tenant']);
        $product = Product::create(['name' => 'Supply', 'price' => 10, 'stock' => 5, 'is_active' => true]);
        Order::create(['user_id' => $tenant->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 10, 'status' => 'pending']);
        $this->actingAs($tenant)->get('/tenant/orders')->assertOk()->assertSee('gcash-qr/supply.png')->assertDontSee('gcash-qr/inactive.png');
        $manager->forceFill(['gcash_qr_path' => null])->save();
        $this->get('/tenant/orders')->assertOk()->assertSee('has not uploaded a QR code yet')->assertSee('Submit for verification');
    }

    public function test_invalid_reference_reopens_only_the_matching_payment_dialog(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $stall = Stall::create(['manager_id' => $manager->id, 'name' => 'Dialog Test', 'price' => 1000, 'status' => 'occupied']);
        $bill = Payment::create(['tenant_id' => $tenant->id, 'manager_id' => $manager->id, 'stall_id' => $stall->id, 'amount' => 1000, 'due_date' => today(), 'status' => 'pending']);
        $this->actingAs($tenant)->from('/tenant/bills')->patch('/billing/payment/'.$bill->id.'/submit', [
            'reference_number' => '123', 'gcash_payment_key' => 'payment-'.$bill->id,
        ])->assertRedirect('/tenant/bills')->assertSessionHasErrors('reference_number')->assertSessionHasInput('reference_number', '123');
        $this->get('/tenant/bills')->assertOk()->assertSee('data-gcash-reopen', false)
            ->assertSee('id="gcash-dialog-payment-'.$bill->id.'"', false)->assertSee('aria-haspopup="dialog"', false);
        $this->assertSame('pending', $bill->fresh()->status->value);
    }
}

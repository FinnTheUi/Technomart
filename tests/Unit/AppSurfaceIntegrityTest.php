<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class AppSurfaceIntegrityTest extends TestCase
{
    public function test_every_literal_route_used_by_a_blade_view_exists(): void
    {
        $missing = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            preg_match_all('/(?<!->)\broute\([\'\"]([^\'\"]+)[\'\"]/', (string) file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $routeName) {
                if (! Route::has($routeName)) {
                    $missing[] = $file->getFilename().': '.$routeName;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Blade views reference missing named routes.');
    }

    public function test_every_growing_record_list_has_backend_and_ui_pagination(): void
    {
        $lists = [
            ['app/Http/Controllers/Admin/UserController.php', 'resources/views/admin/users.blade.php'],
            ['app/Http/Controllers/Admin/ProductController.php', 'resources/views/admin/products.blade.php'],
            ['app/Http/Controllers/Admin/OrderController.php', 'resources/views/admin/orders.blade.php'],
            ['app/Http/Controllers/Admin/InventoryController.php', 'resources/views/admin/inventory.blade.php'],
            ['app/Http/Controllers/Admin/AuditLogController.php', 'resources/views/admin/audit-log.blade.php'],
            ['app/Http/Controllers/Admin/LocationController.php', 'resources/views/admin/locations.blade.php'],
            ['app/Http/Controllers/Admin/SupplierController.php', 'resources/views/admin/suppliers.blade.php'],
            ['app/Http/Controllers/Admin/PurchaseOrderController.php', 'resources/views/admin/purchase-orders.blade.php'],
            ['app/Http/Controllers/Manager/StallController.php', 'resources/views/manager/stalls.blade.php'],
            ['app/Http/Controllers/Manager/ContractController.php', 'resources/views/manager/contracts.blade.php'],
            ['app/Http/Controllers/Manager/PaymentController.php', 'resources/views/manager/payments.blade.php'],
            ['app/Http/Controllers/Manager/ElectricBillController.php', 'resources/views/manager/electric-bills.blade.php'],
            ['app/Http/Controllers/Manager/MaintenanceController.php', 'resources/views/manager/inbox.blade.php'],
            ['app/Http/Controllers/Manager/RentalApplicationController.php', 'resources/views/manager/rental-applications.blade.php'],
            ['app/Http/Controllers/Tenant/OrderController.php', 'resources/views/tenant/orders.blade.php'],
            ['app/Http/Controllers/Tenant/OrderController.php', 'resources/views/tenant/products.blade.php'],
            ['app/Http/Controllers/Tenant/BillController.php', 'resources/views/tenant/bills.blade.php'],
            ['app/Http/Controllers/Tenant/BillController.php', 'resources/views/tenant/electric-bills.blade.php'],
            ['app/Http/Controllers/Tenant/ContractController.php', 'resources/views/tenant/contracts.blade.php'],
            ['app/Http/Controllers/Tenant/MaintenanceController.php', 'resources/views/tenant/maintenance.blade.php'],
        ];

        foreach ($lists as [$controller, $view]) {
            $this->assertStringContainsString('->paginate(', (string) file_get_contents(base_path($controller)), $controller.' is not paginated.');
            $this->assertStringContainsString('->links(', (string) file_get_contents(base_path($view)), $view.' has no pagination controls.');
        }
    }
}

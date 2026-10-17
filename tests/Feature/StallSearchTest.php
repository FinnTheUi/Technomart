<?php

namespace Tests\Feature;

use App\Models\Stall;
use App\Models\User;
use App\Services\Rental\StallLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StallSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'manager']);
        app(StallLayoutService::class)->sync(); // 24 stalls + 10 carts
    }

    private function listed($response): array
    {
        return $response->viewData('stalls')->pluck('name')->all();
    }

    public function test_stall_list_is_paginated(): void
    {
        $page1 = $this->get('/stalls')->assertOk();
        $this->assertCount(6, $this->listed($page1));
        $page1->assertSee('Showing 1–6 of 34');

        $lastPage = $this->get('/stalls?page=6')->assertOk();
        $this->assertCount(4, $this->listed($lastPage)); // 34 stalls = 5 full pages + 4
    }

    public function test_search_by_number_or_name(): void
    {
        $this->assertSame(['Stall 12'], $this->listed($this->get('/stalls?q=stall+12')));
        $this->assertSame(['Stall 7', 'Cart 7'], $this->listed($this->get('/stalls?q=7')));   // not Stall 17
        $this->assertSame(['Cart 3'], $this->listed($this->get('/stalls?q=cart+3')));
        $this->assertSame(['Stall 12'], $this->listed($this->get('/stalls?q=%2312')));
        $this->assertSame(10, $this->get('/stalls?q=cart')->viewData('stalls')->total());
    }

    public function test_search_only_matches_the_stall_name(): void
    {
        Stall::where('name', 'Stall 3')->update(['location' => 'Near entrance', 'size' => '3x3m']);

        $this->assertSame([], $this->listed($this->get('/stalls?q=entrance')));
        $this->assertSame([], $this->listed($this->get('/stalls?q=3x3')));
    }

    public function test_filter_by_floor_and_status(): void
    {
        Stall::where('name', 'Cart 2')->update(['status' => 'maintenance']);

        $this->assertSame(10, $this->get('/stalls?floor=2')->viewData('stalls')->total());
        $this->assertSame(['Cart 2'], $this->listed($this->get('/stalls?floor=2&status=maintenance')));
        $this->assertSame(24, $this->get('/stalls?floor=1&status=available')->viewData('stalls')->total());
    }

    public function test_filters_are_kept_across_pages(): void
    {
        $response = $this->get('/stalls?floor=1');

        $this->assertStringContainsString('floor=1', $response->viewData('stalls')->nextPageUrl());
    }

    public function test_no_results_shows_a_helpful_message(): void
    {
        $this->get('/stalls?q=zzz')->assertOk()->assertSee('No stalls match your search');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->get('/stalls?status=demolished')->assertSessionHasErrors('status');
        $this->get('/stalls?floor=9')->assertSessionHasErrors('floor');
    }

    public function test_wildcards_in_search_are_treated_literally(): void
    {
        $this->assertSame([], $this->listed($this->get('/stalls?q=%25')));
    }
}

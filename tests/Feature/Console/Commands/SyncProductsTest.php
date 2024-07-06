<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SyncProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_products_from_service_a_successfully(): void
    {
        Http::fake([
            'https://5fc7a13cf3c77600165d89a8.mockapi.io/api/v5/products' => Http::response($this->getFakeProductsData(), 200),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Products synced successfully from ServiceA');

        $this->artisan('app:sync-products', ['service' => 'ServiceA'])
            ->assertExitCode(0);
    }


    public function test_it_handles_service_not_found_exception()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Products failed to sync from InvalidService', Mockery::type('array'));

        $this->artisan('app:sync-products', ['service' => 'InvalidService'])
            ->assertExitCode(0);
    }

    public function test_it_handles_external_service_error()
    {
        Http::fake([
            'https://5fc7a13cf3c77600165d89a8.mockapi.io/api/v5/products' => Http::response([], 500),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Products failed to sync from ServiceA', Mockery::type('array'));

        $this->artisan('app:sync-products', ['service' => 'ServiceA'])
            ->assertExitCode(0);
    }

    private function getFakeProductsData()
    {
        return [
            ['id' => 1, 'name' => 'Product 1', 'image' => '...', 'price' => 10.0, 'variations' => [['color' => 'Red', 'material' => 'Wood', 'quantity' => 5, 'additional_price' => 2.0]]],
            ['id' => 2, 'name' => 'Product 2', 'image' => '...', 'price' => 15.0, 'variations' => [['color' => 'Blue', 'material' => 'Metal', 'quantity' => 8, 'additional_price' => 3.0]]],
        ];
    }
}

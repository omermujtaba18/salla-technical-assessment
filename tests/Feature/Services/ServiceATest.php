<?php

namespace Tests\Feature\Services;

use App\Services\ServiceA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServiceATest extends TestCase
{

    use RefreshDatabase;

    protected $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceA();
    }

    public function test_it_syncs_products_successfully()
    {
        Http::fake([
            '*' => Http::response($this->getFakeProductsData(), 200),
        ]);

        $this->service->syncProducts();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variations_types', 4);
        $this->assertDatabaseCount('product_variations', 4);
    }


    public function test_it_handles_external_service_error()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(RequestException::class);
        $this->service->syncProducts();
    }

    private function getFakeProductsData()
    {
        return [
            [
                'id' => 1,
                'name' => 'Product 1',
                'image' => '...',
                'price' => 10.0,
                'variations' => [
                    ['color' => 'Red', 'material' => 'Wood', 'quantity' => 5, 'additional_price' => 2.0]
                ]
            ],
            [
                'id' => 2,
                'name' => 'Product 2',
                'image' => '...',
                'price' => 15.0,
                'variations' => [
                    ['color' => 'Blue', 'material' => 'Metal', 'quantity' => 8, 'additional_price' => 3.0]
                ]
            ],
        ];
    }
}

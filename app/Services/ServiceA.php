<?php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Products;
use Illuminate\Support\Facades\Http;

class ServiceA implements ServiceInterface
{
    protected $name = 'ServiceA';
    protected $baseUrl = 'https://5fc7a13cf3c77600165d89a8.mockapi.io/api/v5';
    protected $headers = [];

    public function syncProducts(): void
    {
        $response = Http::baseUrl($this->baseUrl)->withHeaders($this->headers)->get('/products');
        $response->throw();

        if ($response->successful()) {
            $products = $response->json();
            $productIds = collect($products)->pluck('id')->toArray();

            foreach ($products as $externalProduct) {
                $product = Products::withTrashed()->updateOrCreate(
                    ['id' => $externalProduct['id']],
                    [
                        'name' => $externalProduct['name'], 'image' => $externalProduct['image'], 'price' => $externalProduct['price'],
                        'deleted_at' => NULL, 'deletion_reason' => NULL
                    ]
                );

                $productVariations = $externalProduct['variations'];
                $product->variation_types()->delete();

                if ($productVariations && is_array($productVariations) && !empty($productVariations)) {
                    foreach ($productVariations as $variation) {
                        $colorVariation = $product->variation_types()->create(['variation_type_name' => 'color']);
                        $materialVariation = $product->variation_types()->create(['variation_type_name' => 'material']);

                        $colorVariation->product_variations()->create([
                            'product_variation_name' => $variation['color'],
                            'quantity' => $variation['quantity'], 'additional_price' => $variation['additional_price']
                        ]);
                        $materialVariation->product_variations()->create([
                            'product_variation_name' => $variation['material'],
                            'quantity' => $variation['quantity'], 'additional_price' => $variation['additional_price']
                        ]);
                    }
                }
            }

            Products::whereNotIn('id', $productIds)->delete();
        }
    }

    public function getName(): string
    {
        return $this->name;
    }
}

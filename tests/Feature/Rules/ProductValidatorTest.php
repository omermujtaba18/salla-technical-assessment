<?php

namespace Tests\Feature\Rules;

use App\Rules\ProductValidator;
use Tests\TestCase;

class ProductValidatorTest extends TestCase
{
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductValidator();
    }

    public function test_it_validates_and_returns_valid_data()
    {
        $data = [
            'id' => 1,
            'name' => 'Product A',
            'sku' => 'SKU123',
            'price' => 10.99,
            'currency' => 'SAR',
            'variations' => null,
            'quantity' => '100',
            'status' => 'active',
        ];

        $validatedData = $this->validator->validate($data);

        $this->assertEquals($data, $validatedData);
    }

    public function test_it_converts_empty_strings_to_null_for_nullable_fields()
    {
        $data = [
            'id' => 1,
            'name' => '',
            'sku' => '',
            'price' => '',
            'currency' => '',
            'variations' => '',
            'quantity' => '',
            'status' => '',
        ];

        $validatedData = $this->validator->validate($data);

        $expectedData = [
            'id' => 1,
            'name' => null,
            'sku' => null,
            'price' => null,
            'currency' => null,
            'variations' => null,
            'quantity' => null,
            'status' => null,
        ];

        $this->assertEquals($expectedData, $validatedData);
    }

    public function test_it_converts_invalid_fields_to_null_and_mark_as_incomplete_import(): void
    {
        $data = [
            'id' => 1,
            'name' => 'some-name',
            'sku' => 'some-sku',
            'price' => 'invalid-price', // This will fail the numeric and regex validation
            'currency' => 'SAR',
            'variations' => '',
            'quantity' => '100',
            'status' => 'sale',
        ];

        $validatedData = $this->validator->validate($data);

        $expectedData = [
            'id' => 1,
            'name' => 'some-name',
            'sku' => 'some-sku',
            'price' => null, // Converted to null because of validation failure
            'currency' => 'SAR',
            'variations' => null,
            'status' => 'sale',
            'quantity' => '100',
            'incomplete_import' => true // Marked as incomplete due to validation failure
        ];

        $this->assertEquals($expectedData, $validatedData);
    }
}

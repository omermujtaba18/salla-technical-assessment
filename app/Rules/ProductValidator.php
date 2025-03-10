<?php

namespace App\Rules;

use Illuminate\Support\Facades\Validator;
use Exception;

class ProductValidator
{

    protected $rules = [
        'id' => 'required|integer',
        'name' => 'nullable|string|max:255',
        'sku' => 'nullable|string|max:255',
        'price' => 'nullable|numeric|regex:/^\d{1,5}(\.\d{1,2})?$/',
        'currency' => 'nullable|string|max:20',
        'variations' => 'nullable|string',
        'quantity' => 'nullable|integer',
        'status' => 'nullable|string|max:255',
    ];

    /**
     * Validate product data and set nullable fields to null if validation fails.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function validate(array $data): array
    {
        // Convert empty strings to null for nullable fields
        foreach ($this->rules as $field => $rule) {
            if (strpos($rule, 'nullable') !== false && isset($data[$field]) && trim($data[$field]) === '') {
                $data[$field] = null;
            }
        }

        $validator = Validator::make($data, $this->rules);

        // Check validation failures and set nullable fields to null if they fail
        if ($validator->fails()) {
            foreach ($this->rules as $field => $rule) {
                // Check if the field is nullable and it failed validation
                if (strpos($rule, 'nullable') !== false && $validator->errors()->has($field)) {
                    $data[$field] = null; // Convert the field to null
                }
            }
            $data['incomplete_import'] = true; // Mark as incomplete import due to validation failure
        }


        return $data;
    }
}

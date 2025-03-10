<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariations extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'quantity', 'availability', 'product_variation_name', 'product_variation_type_id', 'additional_price'];

    /**
     * Get the variation_type that owns the variation.
     */
    public function variation_type()
    {
        return $this->belongsTo(ProductVariationsTypes::class);
    }
}

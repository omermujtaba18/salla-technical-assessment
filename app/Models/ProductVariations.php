<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariations extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'color', 'size', 'quantity', 'availability', 'product_id'];

    /**
     * Get the product that owns the variation.
     */
    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}

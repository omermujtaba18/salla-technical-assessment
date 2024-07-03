<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariationsTypes extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'variation_type_name', 'product_id'];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function product_variations(): HasMany
    {
        return $this->hasMany(ProductVariations::class, 'product_variation_type_id');
    }
}

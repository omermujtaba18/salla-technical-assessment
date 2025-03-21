<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'sku', 'product_status_id', 'quantity', 'price', 'product_currency_id', 'incomplete_import', 'deletion_reason', 'image'];

    protected $dates = ['deleted_at'];

    /**
     * Get the variations for the product.
     */
    public function variation_types(): HasMany
    {
        return $this->hasMany(ProductVariationsTypes::class, 'product_id');
    }

    public function status(): HasOne
    {
        return $this->hasOne(ProductStatus::class, 'product_id');
    }

    public function currency(): HasOne
    {
        return $this->hasOne(ProductCurrency::class, 'product_id');
    }
}

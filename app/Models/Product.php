<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'price',
        'description',
        'category',
        'in_stock',
        'show_in_slider',
        'image',
        'attributes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'boolean',
        'show_in_slider' => 'boolean',
        'attributes' => 'array',
    ];

    /**
     * Связь с магазином
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Связь с категорией
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Множественные категории товара
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    /**
     * Проверка наличия товара
     */
    public function isAvailable(): bool
    {
        return $this->in_stock;
    }

    /**
     * Форматированная цена
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, '.', ' ') . ' ₽';
    }

}

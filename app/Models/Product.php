<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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


    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            // Если категория не указана, присваиваем "Без категории"
            if (!$product->category_id) {
                $miscCategory = \App\Models\Category::where('shop_id', $product->shop_id)
                    ->where('name', 'Без категории')
                    ->first();
                
                if ($miscCategory) {
                    $product->category_id = $miscCategory->id;
                }
            }
        });

        static::updating(function ($product) {
            // Если категория не указана при обновлении, присваиваем "Без категории"
            if (!$product->category_id) {
                $miscCategory = \App\Models\Category::where('shop_id', $product->shop_id)
                    ->where('name', 'Без категории')
                    ->first();
                
                if ($miscCategory) {
                    $product->category_id = $miscCategory->id;
                }
            }
        });
    }
}

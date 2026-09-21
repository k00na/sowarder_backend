<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use App\ValueObjects\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'restaurant_id',
    'menu_category_id',
    'name',
    'description',
    'price_minor',
    'image_path',
    'is_available',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToRestaurant, HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_available' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function menuCategory(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function moneyPrice(): Money
    {
        return Money::of($this->price_minor, $this->restaurant->currency);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    protected $fillable = [
        'code', 'barcode', 'name', 'category_id', 'unit', 'cost_price',
        'sell_price', 'reorder_level', 'image', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'reorder_level' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::created(fn () => self::flushCounts());
        static::deleted(fn () => self::flushCounts());
        static::updated(function (Product $p) {
            if ($p->wasChanged(['is_active', 'category_id'])) {
                self::flushCounts();
            }
        });
    }

    /** Cache key for grid row counts; changes whenever the catalogue's shape changes. */
    public static function countCacheKey(string $scope, int|string $filter): string
    {
        return 'products.count.'.Cache::get('products.count.v', 0).".{$scope}.{$filter}";
    }

    /** Invalidate every cached grid count at once (call after bulk DB::table() changes too). */
    public static function flushCounts(): void
    {
        Cache::forever('products.count.v', (int) Cache::get('products.count.v', 0) + 1);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('uploads/'.$this->image) : null;
    }
}

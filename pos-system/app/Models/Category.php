<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $fillable = ['name', 'icon', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('categories.active'));
        static::deleted(fn () => Cache::forget('categories.active'));
    }

    /** Active categories for POS tabs / dropdowns – cached, flushed on change. */
    public static function activeCached()
    {
        return Cache::rememberForever('categories.active', fn () => static::query()
            ->where('is_active', true)->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'icon']));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value store settings. All settings are loaded with ONE query and cached
 * forever; the cache is flushed whenever a setting is saved.
 */
class Setting extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'Hardware Store',
        'store_address' => '',
        'store_phone' => '',
        'currency' => 'LKR',
        'tax_rate' => '0',
        'tax_label' => 'VAT',
        'receipt_footer' => 'Thank you! Come again.',
        'products_per_page' => '10',
        'allow_negative_stock' => '0',
    ];

    public static function allValues(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return array_merge(self::DEFAULTS, static::query()->pluck('value', 'key')->all());
        });
    }

    public static function val(string $key, $default = null)
    {
        return self::allValues()[$key] ?? $default;
    }

    public static function store(array $values): void
    {
        foreach ($values as $k => $v) {
            static::query()->updateOrInsert(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings.all');
    }
}

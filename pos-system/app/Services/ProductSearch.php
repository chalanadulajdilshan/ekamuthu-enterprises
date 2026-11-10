<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Index-friendly product search.
 *
 *  1. Exact code / barcode  -> unique index lookup (what a barcode scanner sends)
 *  2. Words of 3+ chars      -> FULLTEXT boolean prefix search (+word*)
 *  3. Short words (e.g. 5m)  -> LIKE filter applied on top of the FULLTEXT result
 *  4. Only short words       -> LIKE fallback (short input = cheap anyway)
 */
class ProductSearch
{
    // InnoDB default stopwords – they are never indexed, so "+word*" on them would match nothing.
    private const STOPWORDS = ['about', 'are', 'com', 'for', 'from', 'how', 'that', 'the', 'this', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'www'];

    public static function exactId(string $term): ?int
    {
        $term = trim($term);
        if ($term === '') {
            return null;
        }
        $id = DB::table('products')->where('barcode', $term)->value('id')
            ?? DB::table('products')->where('code', $term)->value('id');

        return $id ? (int) $id : null;
    }

    public static function apply(Builder $q, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        if ($id = self::exactId($term)) {
            $q->where('products.id', $id);

            return;
        }

        $ft = [];
        $short = [];
        foreach (preg_split('/\s+/u', mb_strtolower($term)) as $word) {
            $word = trim(preg_replace('/[+\-<>()~*"@\'.,\/\\\\]+/u', ' ', $word));
            foreach (preg_split('/\s+/u', $word) as $w) {
                if ($w === '') {
                    continue;
                }
                if (mb_strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true)) {
                    $ft[] = '+'.$w.'*';
                } else {
                    $short[] = $w;
                }
            }
        }

        if ($ft) {
            $p = px('products');
            $q->whereRaw("MATCH({$p}.name, {$p}.code, {$p}.barcode) AGAINST (? IN BOOLEAN MODE)", [implode(' ', $ft)]);
            foreach ($short as $w) {
                $q->where('products.name', 'like', '%'.self::esc($w).'%');
            }
        } else {
            $q->where(function ($q) use ($term) {
                $q->where('products.name', 'like', '%'.self::esc($term).'%')
                    ->orWhere('products.code', 'like', self::esc($term).'%');
            });
        }
    }

    private static function esc(string $s): string
    {
        return addcslashes($s, '%_\\');
    }
}

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
 *  4. Nothing found by 2/3, or only short words -> substring match on
 *     name / code / barcode (finds "3456" inside "ITM3456")
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

        // A full scanned barcode (8+ chars) is answered by one unique-index read.
        // Shorter exact codes ("3456") fall through, so similar items ("ITM3456") show too.
        if (mb_strlen($term) >= 8 && ($id = self::exactId($term))) {
            $q->where('products.id', $id);

            return;
        }

        // One token containing a digit = a code / barcode / size fragment ("3456", "ITM34",
        // "12mm"). People expect "contains" here, which word-prefix FULLTEXT can't do.
        if (! preg_match('/\s/u', $term) && preg_match('/\d/u', $term)) {
            self::contains($q, [mb_strtolower($term)]);

            return;
        }

        $ft = [];
        $short = [];
        $words = [];
        foreach (preg_split('/\s+/u', mb_strtolower($term)) as $word) {
            $word = trim(preg_replace('/[+\-<>()~*"@\'.,\/\\\\]+/u', ' ', $word));
            foreach (preg_split('/\s+/u', $word) as $w) {
                if ($w === '') {
                    continue;
                }
                $words[] = $w;
                if (mb_strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true)) {
                    $ft[] = '+'.$w.'*';
                } else {
                    $short[] = $w;
                }
            }
        }
        if (! $words) {
            $words = [mb_strtolower($term)]; // input was only punctuation, e.g. "3/4"
        }

        // 1) FULLTEXT word-prefix search (index-backed) - used when it finds anything.
        if ($ft) {
            $p = px('products');
            $applyFt = function ($q) use ($p, $ft, $short) {
                $q->whereRaw("MATCH({$p}.name, {$p}.code, {$p}.barcode) AGAINST (? IN BOOLEAN MODE)", [implode(' ', $ft)]);
                foreach ($short as $w) {
                    $q->where('products.name', 'like', '%'.self::esc($w).'%');
                }
            };
            $probe = clone $q; // keeps the caller's filters (active, category)
            $applyFt($probe);
            if ($probe->exists()) {
                $applyFt($q);

                return;
            }
        }

        // 2) Substring fallback: every word must appear somewhere in name, code or barcode.
        //    Catches what word-prefix search can't: "3456" inside "ITM3456", part of a
        //    barcode, etc. It scans, but only runs when step 1 found nothing.
        self::contains($q, $words);
    }

    /** Every word must appear somewhere in name, code or barcode. */
    private static function contains(Builder $q, array $words): void
    {
        foreach ($words as $w) {
            $like = '%'.self::esc($w).'%';
            $q->where(function ($q) use ($like) {
                $q->where('products.name', 'like', $like)
                    ->orWhere('products.code', 'like', $like)
                    ->orWhere('products.barcode', 'like', $like);
            });
        }
    }

    private static function esc(string $s): string
    {
        return addcslashes($s, '%_\\');
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free, race-safe document numbers (bills, purchases).
 *
 * Uses MySQL's LAST_INSERT_ID(expr) trick: the UPDATE increments the counter and
 * row-locks it until the surrounding transaction commits, so two cashiers can
 * never receive the same bill number – and it costs a single round trip.
 */
class DocNumber
{
    public static function next(string $key, string $prefix, int $pad = 6): string
    {
        DB::table('doc_sequences')->insertOrIgnore(['key' => $key, 'last_no' => 0]);
        DB::update('UPDATE '.px('doc_sequences').' SET last_no = LAST_INSERT_ID(last_no + 1) WHERE `key` = ?', [$key]);
        $n = (int) DB::selectOne('SELECT LAST_INSERT_ID() AS n')->n;

        return self::format($prefix, $n, $pad);
    }

    /** Preview of the next number (display only – not reserved). */
    public static function peek(string $key, string $prefix, int $pad = 6): string
    {
        $n = (int) DB::table('doc_sequences')->where('key', $key)->value('last_no') + 1;

        return self::format($prefix, $n, $pad);
    }

    private static function format(string $prefix, int $n, int $pad): string
    {
        return $prefix.'-'.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}

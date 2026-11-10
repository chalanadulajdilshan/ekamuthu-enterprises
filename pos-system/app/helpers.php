<?php

if (! function_exists('qty')) {
    /** 12.500 -> "12.5", 3.000 -> "3", 0 -> "0" */
    function qty($n, int $decimals = 3): string
    {
        $s = number_format((float) $n, $decimals);

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}

if (! function_exists('px')) {
    /**
     * Prefixed table name / alias for use inside RAW SQL fragments.
     * All POS tables live in the shared Ekamuthu database with a "pos_" prefix
     * (config: database.connections.mysql.prefix). The query builder adds it
     * automatically, raw strings do not - so raw SQL must go through px().
     */
    function px(string $name): string
    {
        return \Illuminate\Support\Facades\DB::getTablePrefix().$name;
    }
}

if (! function_exists('money')) {
    function money($n): string
    {
        return number_format((float) $n, 2);
    }
}

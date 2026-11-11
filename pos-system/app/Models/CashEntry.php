<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashEntry extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];

    /** type => [label, direction] */
    public const TYPES = [
        'float' => ['Opening float / cash added', 'in'],
        'withdrawal' => ['Bank withdrawal', 'in'],
        'income' => ['Other income', 'in'],
        'expense' => ['Expense', 'out'],
        'deposit' => ['Bank deposit', 'out'],
        'drawing' => ['Owner drawing', 'out'],
        'other' => ['Other payment', 'out'],
    ];

    public static function typesFor(string $direction): array
    {
        return array_map(fn ($t) => $t[0], array_filter(self::TYPES, fn ($t) => $t[1] === $direction));
    }
}

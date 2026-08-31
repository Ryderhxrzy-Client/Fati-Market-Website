<?php

namespace App\Support;

/**
 * Peso amounts for display.
 *
 * The API hands money over as exact decimal strings ("250.00") because the
 * backend owns every calculation. This only renders what it was given - it
 * never adds, discounts or converts anything - and says so plainly when a
 * price has not been set yet, rather than printing a misleading zero.
 */
class Peso
{
    public static function format(mixed $amount, string $whenMissing = '—'): string
    {
        if ($amount === null || $amount === '' || !is_numeric($amount)) {
            return $whenMissing;
        }

        return '₱' . number_format((float) $amount, 2);
    }
}

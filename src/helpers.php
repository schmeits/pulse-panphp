<?php

use Illuminate\Support\Number;

if (! function_exists('toHumanReadableNumber')) {
    /**
     * Convert a number to a human-readable version
     */
    function toHumanReadableNumber(int $number): string
    {
        return Number::format($number);
    }
}

if (! function_exists('toHumanReadablePercentage')) {
    /**
     * Divide 2 values and present them as a human-readable percentage
     */
    function toHumanReadablePercentage(int $total, int $part): string
    {
        if ($total === 0) {
            return 'Infinity%';
        }

        return Number::percentage($part / $total * 100, 0, 1);
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;

class FormatsDates
{
    public static function formatDate(mixed $date, string $format = 'Y-m-d', string $placeholder = 'No Expiry'): string
    {
        if ($date === null || $date === '') {
            return $placeholder;
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format($format);
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Throwable) {
            return (string) $date;
        }
    }
}

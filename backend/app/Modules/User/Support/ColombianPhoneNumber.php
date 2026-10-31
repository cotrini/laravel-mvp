<?php

namespace App\Modules\User\Support;

class ColombianPhoneNumber
{
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '57') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/^3\d{9}$/', $digits) !== 1) {
            return null;
        }

        return '+57'.$digits;
    }
}

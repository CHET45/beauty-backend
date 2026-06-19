<?php

namespace App\Support;

final class PhoneNumber
{
    private const MIN_LOCAL_DIGITS = 6;

    private const MIN_TOTAL_DIGITS = 8;

    private const MAX_TOTAL_DIGITS = 15;

    private const ASCENDING_DIGITS = '01234567890123456789';

    private const DESCENDING_DIGITS = '98765432109876543210';

    public static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function isValidCountryCode(string $countryCode): bool
    {
        return preg_match('/^\+[1-9][0-9]{0,3}$/', $countryCode) === 1;
    }

    public static function isValid(?string $countryCode, string $number): bool
    {
        $number = trim($number);

        if ($number === '' || preg_match('/^[0-9\s().-]+$/', $number) !== 1) {
            return false;
        }

        if ($countryCode !== null && $countryCode !== '' && ! self::isValidCountryCode($countryCode)) {
            return false;
        }

        $localDigits = self::digits($number);
        $countryDigits = self::digits((string) $countryCode);
        $totalLength = strlen($countryDigits) + strlen($localDigits);

        if (
            strlen($localDigits) < self::MIN_LOCAL_DIGITS
            || $totalLength < self::MIN_TOTAL_DIGITS
            || $totalLength > self::MAX_TOTAL_DIGITS
        ) {
            return false;
        }

        return ! self::looksLikePlaceholder($localDigits);
    }

    private static function looksLikePlaceholder(string $digits): bool
    {
        if (preg_match('/^(\d)\1+$/', $digits) === 1) {
            return true;
        }

        if (count(array_unique(str_split($digits))) < 3) {
            return true;
        }

        return strlen($digits) >= self::MIN_LOCAL_DIGITS
            && (
                str_contains(self::ASCENDING_DIGITS, $digits)
                || str_contains(self::DESCENDING_DIGITS, $digits)
            );
    }
}

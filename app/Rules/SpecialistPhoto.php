<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SpecialistPhoto implements ValidationRule
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be an image.');

            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        if (! preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', $value, $matches)) {
            $fail('The :attribute must be a PNG, JPEG, or WebP image.');

            return;
        }

        $decoded = base64_decode($matches[2], true);
        if ($decoded === false || strlen($decoded) > self::MAX_BYTES) {
            $fail('The :attribute must not be larger than 5 MB.');

            return;
        }

        $expectedMime = [
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ][$matches[1]];
        $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($decoded);
        if ($actualMime !== $expectedMime) {
            $fail('The :attribute contents do not match its image type.');
        }
    }
}

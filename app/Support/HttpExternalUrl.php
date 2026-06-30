<?php

namespace App\Support;

/**
 * Validates and normalizes external HTTP/HTTPS URLs (People website field).
 */
final class HttpExternalUrl
{
    private const BLOCKED_SCHEMES = [
        'javascript',
        'data',
        'vbscript',
        'file',
        'mailto',
        'ftp',
    ];

    public static function normalizeForStorage(?string $value): ?string
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            return null;
        }

        return self::validateNormalizedUrl($normalized) ? $normalized : null;
    }

    public static function normalizeForOutput(?string $value): ?string
    {
        return self::normalizeForStorage($value);
    }

    public static function isValid(?string $value): bool
    {
        return self::normalizeForStorage($value) !== null;
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace(["\r", "\n", "\0"], '', $value);
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/u', $value)) {
            return null;
        }

        $lower = strtolower($value);
        foreach (self::BLOCKED_SCHEMES as $scheme) {
            if (str_starts_with($lower, $scheme.':')) {
                return null;
            }
        }

        if (str_starts_with($lower, '//')) {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            if (! self::looksLikeBareHostname($value)) {
                return null;
            }
            $value = 'https://'.$value;
        }

        return $value;
    }

    private static function looksLikeBareHostname(string $value): bool
    {
        if (str_contains($value, ' ') || str_contains($value, '@')) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?(\:[0-9]+)?(\/.*)?$/i', $value);
    }

    private static function validateNormalizedUrl(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '[::1]', '0.0.0.0'], true)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

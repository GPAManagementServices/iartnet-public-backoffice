<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Path ammessi per Curator: prefisso media/, niente traversal o backslash.
 */
class MediaCuratorPathRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        if (str_contains($value, '\\')) {
            Log::warning('media.sign.suspicious_path', [
                'reason' => 'backslash',
                'path_preview' => Str::limit($value, 160, ''),
            ]);
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        $normalized = rawurldecode($value);
        $clean = ltrim($normalized, '/');

        if ($clean === '') {
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        if (str_contains($clean, '..')) {
            Log::warning('media.sign.suspicious_path', [
                'reason' => 'path_traversal',
                'path_preview' => Str::limit($clean, 160, ''),
            ]);
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        if (! str_starts_with($clean, 'media/')) {
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        if (strlen($clean) > 2048) {
            $fail(__('The :attribute must be a valid media path.'));

            return;
        }

        if (! preg_match('/^media\/[A-Za-z0-9_\-\.\/]+$/', $clean)) {
            $fail(__('The :attribute must be a valid media path.'));
        }
    }
}

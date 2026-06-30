<?php

namespace App\Support;

use App\Models\Person;

/**
 * Determines whether a published person has a reachable public detail view.
 *
 * Mirrors the frontend editorial rule: published + non-empty slug + shortbio with meaningful text.
 */
final class PersonDetailPageResolver
{
    public static function hasDetailPage(Person $person, ?string $locale = null): bool
    {
        if ($person->status !== 'published') {
            return false;
        }

        $locale = $locale ?? app()->getLocale();
        $slug = trim((string) $person->getTranslation('slug', $locale));
        if ($slug === '') {
            return false;
        }

        return self::shortbioHasMeaningfulText($person->getTranslation('shortbio', $locale));
    }

    public static function shortbioHasMeaningfulText(mixed $shortbio): bool
    {
        if (! is_string($shortbio)) {
            return false;
        }

        $text = strip_tags($shortbio);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text) !== '';
    }
}

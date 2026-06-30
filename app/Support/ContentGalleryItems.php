<?php

namespace App\Support;

/**
 * Normalizza la gallery salvata su Activity/Project: lista di oggetti
 * { media_id|id, caption: { it?, en? } } con supporto al formato legacy (solo ID).
 */
final class ContentGalleryItems
{
    /**
     * @return list<array{id: int, caption: array{it: ?string, en: ?string}}>
     */
    public static function normalize(mixed $gallery): array
    {
        if (! is_array($gallery) || $gallery === []) {
            return [];
        }

        if (self::looksLikeStructuredItems($gallery)) {
            return self::normalizeStructured($gallery);
        }

        return self::legacyFlatIdsToItems($gallery);
    }

    /**
     * @param  list<array{id: int, caption: array{it: ?string, en: ?string}}>  $items
     * @return list<int>
     */
    public static function mediaIds(array $items): array
    {
        return collect($items)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array{it?: ?string, en?: ?string}  $captions
     */
    public static function captionForLocale(array $captions, string $locale, ?string $fallbackMediaCaption): ?string
    {
        $it = isset($captions['it']) && is_string($captions['it']) ? trim($captions['it']) : '';
        $en = isset($captions['en']) && is_string($captions['en']) ? trim($captions['en']) : '';

        $fromContent = match ($locale) {
            'it' => $it !== '' ? $it : null,
            'en' => $en !== '' ? $en : null,
            default => null,
        };

        if ($fromContent !== null) {
            return $fromContent;
        }

        if (is_string($fallbackMediaCaption) && trim($fallbackMediaCaption) !== '') {
            return trim($fallbackMediaCaption);
        }

        return null;
    }

    private static function looksLikeStructuredItems(array $gallery): bool
    {
        foreach ($gallery as $el) {
            if (! is_array($el)) {
                return false;
            }
            if (isset($el['id']) && is_numeric($el['id'])) {
                return true;
            }
            if (isset($el['media_id']) && is_numeric($el['media_id'])) {
                return true;
            }
            if (isset($el['image_id']) && is_numeric($el['image_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{id: int, caption: array{it: ?string, en: ?string}}>
     */
    private static function normalizeStructured(array $gallery): array
    {
        $out = [];

        foreach ($gallery as $el) {
            if (! is_array($el)) {
                continue;
            }

            $rawId = $el['media_id'] ?? $el['image_id'] ?? $el['id'] ?? null;
            if (! is_numeric($rawId)) {
                continue;
            }

            $cap = $el['caption'] ?? [];
            $it = isset($cap['it']) && is_string($cap['it']) ? ($cap['it'] !== '' ? $cap['it'] : null) : null;
            $en = isset($cap['en']) && is_string($cap['en']) ? ($cap['en'] !== '' ? $cap['en'] : null) : null;

            $out[] = [
                'id' => (int) $rawId,
                'caption' => ['it' => $it, 'en' => $en],
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: int, caption: array{it: ?string, en: ?string}}>
     */
    private static function legacyFlatIdsToItems(array $gallery): array
    {
        $ids = collect($gallery)
            ->flatten()
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        return $ids
            ->map(fn (int $id) => [
                'id' => $id,
                'caption' => ['it' => null, 'en' => null],
            ])
            ->all();
    }

    /**
     * Formato persistito su DB / Filament: media_id + caption.
     *
     * @param  list<array{id: int, caption: array{it: ?string, en: ?string}}>  $items
     * @return list<array{media_id: int, caption: array{it: ?string, en: ?string}}>
     */
    public static function toPersisted(array $items): array
    {
        return collect($items)
            ->map(fn (array $row) => [
                'media_id' => (int) $row['id'],
                'caption' => [
                    'it' => $row['caption']['it'] ?? null,
                    'en' => $row['caption']['en'] ?? null,
                ],
            ])
            ->all();
    }
}

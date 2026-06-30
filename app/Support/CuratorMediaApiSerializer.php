<?php

namespace App\Support;

use Awcodes\Curator\Models\Media;

/**
 * Serializzazione media Curator per le API, con alt e didascalie editoriali (IT/EN) separate.
 */
final class CuratorMediaApiSerializer
{
    /**
     * @param  array{it?: ?string, en?: ?string}|null  $contentCaptionsTranslations  Didascalie sul contenuto (Activity/Project/…); null = solo metadati libreria
     * @return array<string, mixed>|null
     */
    public static function serialize(
        ?Media $media,
        ?string $altOverride,
        string $locale,
        ?array $contentCaptionsTranslations = null
    ): ?array {
        if (! $media) {
            return null;
        }

        $base = [
            'id' => $media->id,
            'url' => $media->url,
            'path' => $media->path,
            'alt' => $altOverride ?? $media->alt,
            'title' => $media->title,
            'description' => $media->description,
            'width' => $media->width,
            'height' => $media->height,
            'size' => $media->size,
            'type' => $media->type,
            'name' => $media->name,
        ];

        if ($contentCaptionsTranslations === null) {
            $base['caption'] = $media->caption;
            $base['captions'] = null;

            return $base;
        }

        $caps = [
            'it' => self::stringOrNull($contentCaptionsTranslations['it'] ?? null),
            'en' => self::stringOrNull($contentCaptionsTranslations['en'] ?? null),
        ];

        $base['captions'] = [
            'it' => $caps['it'],
            'en' => $caps['en'],
        ];
        $base['caption'] = ContentGalleryItems::captionForLocale($caps, $locale, $media->caption);

        return $base;
    }

    private static function stringOrNull(mixed $v): ?string
    {
        if (! is_string($v)) {
            return null;
        }

        $t = trim($v);

        return $t === '' ? null : $t;
    }
}

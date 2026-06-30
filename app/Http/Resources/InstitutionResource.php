<?php

namespace App\Http\Resources;

use Awcodes\Curator\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', app()->getLocale());

        $serializeMedia = function (?Media $media, ?string $altOverride = null): ?array {
            if (! $media) {
                return null;
            }

            return [
                'id' => $media->id,
                'url' => $media->url,
                'path' => $media->path, // <- Aggiunto path per l'endpoint Signer
                'alt' => $altOverride ?? $media->alt,
                'title' => $media->title,
                'caption' => $media->caption,
                'description' => $media->description,
                'width' => $media->width,
                'height' => $media->height,
                'size' => $media->size,
                'type' => $media->type,
            ];
        };

        $logoAlt = $this->getTranslation('logo_image_alt', $locale, false) ?: null;
        $coverAlt = $this->getTranslation('cover_image_alt', $locale, false) ?: null;
        $ogAlt = $this->getTranslation('opengraph_picture_alt', $locale, false) ?: null;

        $slugEn = (string) ($this->slug_en ?? '');
        $slugIt = (string) ($this->slug_it ?? '');
        $displaySortKey = $slugEn !== '' ? $slugEn : ($slugIt !== '' ? $slugIt : (string) ($this->getTranslation('slug', 'en') ?: $this->getTranslation('slug', 'it') ?: ''));

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale),
            'slug' => $this->getTranslation('slug', $locale),
            'slug_en' => $this->slug_en,
            'slug_it' => $this->slug_it,
            'display_sort_key' => $displaySortKey,
            'status' => $this->status,
            'website' => $this->website,

            'description' => $this->getTranslation('description', $locale),

            'logo_image_id' => $this->logo_image_id,
            'cover_image_id' => $this->cover_image_id,
            'opengraph_picture_id' => $this->opengraph_picture_id,

            'media' => [
                'logo' => $serializeMedia(
                    $this->relationLoaded('logoImage') ? $this->logoImage : null,
                    $logoAlt
                ),

                'cover_image' => $serializeMedia(
                    $this->relationLoaded('coverImage') ? $this->coverImage : null,
                    $coverAlt
                ),

                'opengraph_picture' => $serializeMedia(
                    $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : null,
                    $ogAlt
                ),
            ],

            'categories' => $this->whenLoaded('categories', function () use ($locale) {
                return $this->categories
                    ->map(fn ($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->getTranslation('name', $locale),
                        'slug' => $cat->getTranslation('slug', $locale),
                        'type' => $cat->type,
                    ])
                    ->values()
                    ->all();
            }),

            'meta' => [
                'title' => $this->getTranslation('meta_title', $locale),
                'description' => $this->getTranslation('meta_description', $locale),
                'opengraph_title' => $this->getTranslation('opengraph_title', $locale),
                'opengraph_description' => $this->getTranslation('opengraph_description', $locale),
                'opengraph_picture_alt' => $this->getTranslation('opengraph_picture_alt', $locale),
            ],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'translations' => [
                'name' => $this->getTranslations('name'),
                'slug' => $this->getTranslations('slug'),
                'description' => $this->getTranslations('description'),
                'meta_title' => $this->getTranslations('meta_title'),
                'meta_description' => $this->getTranslations('meta_description'),
                'opengraph_title' => $this->getTranslations('opengraph_title'),
                'opengraph_description' => $this->getTranslations('opengraph_description'),
                'opengraph_picture_alt' => $this->getTranslations('opengraph_picture_alt'),

                'logo_image_alt' => $this->getTranslations('logo_image_alt'),
                'cover_image_alt' => $this->getTranslations('cover_image_alt'),
            ],
        ];
    }
}

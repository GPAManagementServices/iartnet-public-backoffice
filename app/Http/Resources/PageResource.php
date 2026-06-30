<?php

namespace App\Http\Resources;

use App\Support\RichTextSanitizer;
use Awcodes\Curator\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
                'name' => $media->name,
            ];
        };

        $cover = $this->relationLoaded('coverImage') ? $this->coverImage : $this->coverImage()->first();
        $og = $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : $this->opengraphPicture()->first();

        $coverAlt = method_exists($this->resource, 'getTranslation')
            ? ($this->getTranslation('cover_image_alt', $locale) ?: null)
            : null;

        $ogAlt = method_exists($this->resource, 'getTranslation')
            ? ($this->getTranslation('opengraph_picture_alt', $locale) ?: null)
            : null;

        $description = method_exists($this->resource, 'getTranslation')
            ? $this->getTranslation('description', $locale)
            : ($this->description ?? null);

        $descriptionTranslations = method_exists($this->resource, 'getTranslations')
            ? RichTextSanitizer::stripTrixAttachmentLinksFromTranslations($this->getTranslations('description'))
            : [];

        return [
            'id' => $this->id,

            'title' => method_exists($this->resource, 'getTranslation')
                ? $this->getTranslation('title', $locale)
                : ($this->title ?? null),

            // slug principale in base alla locale
            'slug' => $locale === 'it'
                ? ($this->slug_it ?? null)
                : ($this->slug_en ?? null),

            'status' => $this->status ?? null,

            // HTML pulito (niente <a> / <figcaption> attorno alle immagini Trix)
            'description' => RichTextSanitizer::stripTrixAttachmentLinks($description),

            'media' => [
                'cover_image' => $serializeMedia($cover, $coverAlt),
                'opengraph_picture' => $serializeMedia($og, $ogAlt),
            ],

            'meta' => [
                'title' => method_exists($this->resource, 'getTranslation') ? $this->getTranslation('meta_title', $locale) : ($this->meta_title ?? null),
                'description' => method_exists($this->resource, 'getTranslation') ? $this->getTranslation('meta_description', $locale) : ($this->meta_description ?? null),
                'opengraph_title' => method_exists($this->resource, 'getTranslation') ? $this->getTranslation('opengraph_title', $locale) : ($this->opengraph_title ?? null),
                'opengraph_description' => method_exists($this->resource, 'getTranslation') ? $this->getTranslation('opengraph_description', $locale) : ($this->opengraph_description ?? null),
                'opengraph_picture_alt' => method_exists($this->resource, 'getTranslation') ? $this->getTranslation('opengraph_picture_alt', $locale) : ($this->opengraph_picture_alt ?? null),
            ],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'translations' => [
                'title' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('title') : [],
                'slug' => [
                    'en' => $this->slug_en ?? null,
                    'it' => $this->slug_it ?? null,
                ],

                'description' => $descriptionTranslations,

                'meta_title' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('meta_title') : [],
                'meta_description' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('meta_description') : [],
                'opengraph_title' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('opengraph_title') : [],
                'opengraph_description' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('opengraph_description') : [],
                'opengraph_picture_alt' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('opengraph_picture_alt') : [],
                'cover_image_alt' => method_exists($this->resource, 'getTranslations') ? $this->getTranslations('cover_image_alt') : [],
            ],
        ];
    }
}

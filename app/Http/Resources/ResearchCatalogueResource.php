<?php

namespace App\Http\Resources;

use App\Support\CuratorMediaApiSerializer;
use Illuminate\Http\Resources\Json\JsonResource;

class ResearchCatalogueResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', app()->getLocale());

        $cover = $this->relationLoaded('coverImage') ? $this->coverImage : $this->coverImage()->first();
        $og = $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : $this->opengraphPicture()->first();

        $coverAlt = $this->getTranslation('cover_image_alt', $locale) ?: null;
        $ogAlt = $this->getTranslation('opengraph_picture_alt', $locale) ?: null;

        $coverCaptionTranslations = [
            'it' => $this->getTranslation('cover_image_caption', 'it'),
            'en' => $this->getTranslation('cover_image_caption', 'en'),
        ];

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'slug' => $locale === 'it' ? $this->slug_it : $this->slug_en,
            'status' => $this->status,

            'author' => $this->author,
            'external_link' => $this->external_link,

            'description' => $this->getTranslation('description', $locale),

            'media' => [
                'cover_image' => CuratorMediaApiSerializer::serialize($cover, $coverAlt, $locale, $coverCaptionTranslations),
                'opengraph_picture' => CuratorMediaApiSerializer::serialize($og, $ogAlt, $locale, null),
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
                'title' => $this->getTranslations('title'),
                'description' => $this->getTranslations('description'),
                'meta_title' => $this->getTranslations('meta_title'),
                'meta_description' => $this->getTranslations('meta_description'),
                'opengraph_title' => $this->getTranslations('opengraph_title'),
                'opengraph_description' => $this->getTranslations('opengraph_description'),
                'opengraph_picture_alt' => $this->getTranslations('opengraph_picture_alt'),
                'cover_image_alt' => $this->getTranslations('cover_image_alt'),
                'cover_image_caption' => $this->getTranslations('cover_image_caption'),
            ],

        ];
    }
}

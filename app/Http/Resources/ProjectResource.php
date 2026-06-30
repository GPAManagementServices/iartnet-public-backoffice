<?php

namespace App\Http\Resources;

use App\Models\Institution;
use App\Models\Person;
use App\Support\ContentGalleryItems;
use App\Support\CuratorMediaApiSerializer;
use Awcodes\Curator\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale', app()->getLocale());

        $ogAlt = $this->getTranslation('opengraph_picture_alt', $locale) ?: null;
        $coverAlt = $this->getTranslation('cover_image_alt', $locale) ?: null;

        $coverCaptionTranslations = [
            'it' => $this->getTranslation('cover_image_caption', 'it'),
            'en' => $this->getTranslation('cover_image_caption', 'en'),
        ];

        $cover = $this->relationLoaded('coverImage') ? $this->coverImage : $this->coverImage()->first();
        $og = $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : $this->opengraphPicture()->first();

        $galleryItems = ContentGalleryItems::normalize($this->gallery ?? null);
        $galleryIds = collect(ContentGalleryItems::mediaIds($galleryItems));

        $galleryById = $galleryIds->isNotEmpty()
            ? Media::whereIn('id', $galleryIds->all())->get()->keyBy('id')
            : collect();

        $gallery = collect($galleryItems)
            ->map(function (array $row) use ($galleryById, $locale) {
                $media = $galleryById->get($row['id']);

                return CuratorMediaApiSerializer::serialize(
                    $media,
                    null,
                    $locale,
                    $row['caption']
                );
            })
            ->filter()
            ->values()
            ->all();

        /*
         |----------------------------------------
         | Institutions (ids + resolved objects)
         |----------------------------------------
         */
        $institutionIds = is_array($this->institutions) ? $this->institutions : [];

        $institutions = empty($institutionIds)
            ? []
            : Institution::whereIn('id', $institutionIds)
                ->get()
                ->map(function ($inst) use ($locale) {
                    return [
                        'id' => $inst->id,

                        'name' => $inst->getTranslation('name', $locale),
                        'name_translations' => $inst->getTranslations('name'),

                        'slug' => $inst->getTranslation('slug', $locale),
                        'slug_translations' => $inst->getTranslations('slug'),
                    ];
                })
                ->values()
                ->all();

        /*
         |----------------------------------------
         | People groups (raw groups + resolved people)
         |----------------------------------------
         */
        $peopleGroups = is_array($this->people) ? $this->people : [];

        $allPeopleIds = collect($peopleGroups)
            ->pluck('people_ids')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        $peopleById = empty($allPeopleIds)
            ? collect()
            : Person::whereIn('id', $allPeopleIds)->get()->keyBy('id');

        $people_groups = collect($peopleGroups)
            ->map(function ($group) use ($locale, $peopleById) {
                $ids = is_array($group['people_ids'] ?? null) ? $group['people_ids'] : [];

                $people = collect($ids)
                    ->map(fn ($id) => $peopleById->get($id))
                    ->filter()
                    ->values()
                    ->map(function ($person) use ($locale) {
                        return [
                            'id' => $person->id,

                            'first_name' => $person->getTranslation('first_name', $locale),
                            'first_name_translations' => $person->getTranslations('first_name'),

                            'last_name' => $person->getTranslation('last_name', $locale),
                            'last_name_translations' => $person->getTranslations('last_name'),
                        ];
                    })
                    ->all();

                return [
                    'label' => $group['label'][$locale] ?? null,
                    'label_translations' => $group['label'] ?? [],

                    'people_ids' => $ids,
                    'people' => $people,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $this->id,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'title' => $this->getTranslation('title', $locale),
            'title_translations' => $this->getTranslations('title'),

            'subtitle' => $this->getTranslation('subtitle', $locale),
            'subtitle_translations' => $this->getTranslations('subtitle'),

            'slug' => $this->getTranslation('slug', $locale),
            'slug_translations' => $this->getTranslations('slug'),

            'status' => $this->status,

            'meta_title' => $this->getTranslation('meta_title', $locale),
            'meta_title_translations' => $this->getTranslations('meta_title'),

            'meta_description' => $this->getTranslation('meta_description', $locale),
            'meta_description_translations' => $this->getTranslations('meta_description'),

            'opengraph_title' => $this->getTranslation('opengraph_title', $locale),
            'opengraph_title_translations' => $this->getTranslations('opengraph_title'),

            'opengraph_description' => $this->getTranslation('opengraph_description', $locale),
            'opengraph_description_translations' => $this->getTranslations('opengraph_description'),

            'cover_image_id' => $this->cover_image_id,
            'cover_image' => CuratorMediaApiSerializer::serialize($cover, $coverAlt, $locale, $coverCaptionTranslations),
            'cover_image_alt' => $this->getTranslation('cover_image_alt', $locale),
            'cover_image_alt_translations' => $this->getTranslations('cover_image_alt'),
            'cover_image_caption' => $this->getTranslation('cover_image_caption', $locale),
            'cover_image_caption_translations' => $this->getTranslations('cover_image_caption'),

            'opengraph_picture_id' => $this->opengraph_picture_id,
            'opengraph_picture' => CuratorMediaApiSerializer::serialize($og, $ogAlt, $locale, null),
            'opengraph_picture_alt' => $this->getTranslation('opengraph_picture_alt', $locale),
            'opengraph_picture_alt_translations' => $this->getTranslations('opengraph_picture_alt'),

            'description' => $this->getTranslation('description', $locale),
            'description_translations' => $this->getTranslations('description'),

            'gallery_ids' => $galleryIds->all(),
            'gallery' => $gallery,

            // ✅ nuovi campi
            'institution_ids' => $institutionIds,
            'institutions' => $institutions,

            // ✅ qui la correzione: variabile giusta con people risolte
            'people_groups' => $people_groups,

            'categories' => $this->whenLoaded('categories', function () use ($locale) {
                return $this->categories
                    ->map(function ($category) use ($locale) {
                        return [
                            'id' => $category->id,

                            'name' => $category->getTranslation('name', $locale),
                            'name_translations' => $category->getTranslations('name'),

                            'slug' => $category->getTranslation('slug', $locale),
                            'slug_translations' => $category->getTranslations('slug'),

                            'type' => $category->type,
                        ];
                    })
                    ->values()
                    ->all();
            }),
        ];
    }
}

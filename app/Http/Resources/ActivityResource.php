<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Institution;
use App\Models\Person;
use App\Support\ActivityAttachmentsPresenter;
use App\Support\ActivityVideoUrls;
use App\Support\ContentGalleryItems;
use App\Support\CuratorMediaApiSerializer;
use Awcodes\Curator\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', app()->getLocale());

        $peopleGroups = $this->people ?? [];

        // tutti gli ID persone in un array piatto
        $allPeopleIds = collect($peopleGroups)
            ->pluck('people_ids')   // [[1,2,3], [4], ...]
            ->flatten()             // [1,2,3,4,...]
            ->filter()              // rimuove null / vuoti
            ->unique()
            ->values()
            ->all();

        // carico tutte le persone e le indicizzo per id
        $peopleById = Person::whereIn('id', $allPeopleIds)
            ->get()
            ->keyBy('id');

        $peopleGroupsArray = collect($peopleGroups)->map(function (array $group) use ($locale, $peopleById) {
            $labelTranslations = $group['label'] ?? [];
            $currentLabel = $labelTranslations[$locale] ?? reset($labelTranslations) ?? null;

            $ids = collect($group['people_ids'] ?? [])
                ->filter()
                ->values()
                ->all();

            $people = collect($ids)->map(function ($id) use ($peopleById, $locale) {
                $person = $peopleById->get($id);

                if (! $person) {
                    return null;
                }

                return [
                    'id' => $person->id,
                    'first_name' => $person->getTranslation('first_name', $locale),
                    'last_name' => $person->getTranslation('last_name', $locale),
                    'slug' => $person->getTranslation('slug', $locale),
                    'status' => $person->status,
                ];
            })->filter()->values();

            return [
                'label' => $currentLabel,
                'label_translations' => $labelTranslations,
                'people' => $people,
            ];
        })->values();

        // institutions salvate come array di ID
        $institutionIds = $this->institutions ?? [];
        $institutions = Institution::whereIn('id', $institutionIds)->get();

        $cover = $this->relationLoaded('coverImage') ? $this->coverImage : $this->coverImage()->first();
        $og = $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : $this->opengraphPicture()->first();

        $coverAlt = $this->getTranslation('cover_image_alt', $locale) ?: null;
        $ogAlt = $this->getTranslation('opengraph_picture_alt', $locale) ?: null;

        $coverCaptionTranslations = [
            'it' => $this->getTranslation('cover_image_caption', 'it'),
            'en' => $this->getTranslation('cover_image_caption', 'en'),
        ];

        $galleryItems = ContentGalleryItems::normalize($this->gallery ?? null);
        $galleryIds = ContentGalleryItems::mediaIds($galleryItems);

        $galleryById = count($galleryIds) > 0
            ? Media::whereIn('id', $galleryIds)->get()->keyBy('id')
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
            ->values();

        $categories = $this->whenLoaded('categories')
            ? $this->categories
            : $this->categories()->get();

        $attachmentsPayload = ActivityAttachmentsPresenter::forApi(
            is_array($this->attachments) ? $this->attachments : null
        );

        $videoUrlsForApi = ActivityVideoUrls::normalizeForStorage(
            is_array($this->video_urls) ? $this->video_urls : null
        ) ?? [];

        if ($videoUrlsForApi === [] && is_string($this->video_url) && trim($this->video_url) !== '') {
            $videoUrlsForApi = ActivityVideoUrls::legacyScalarToJsonArray($this->video_url) ?? [];
        }

        $videoUrlsForApi = array_values($videoUrlsForApi);

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', $locale),
            'slug' => $this->getTranslation('slug', $locale),
            'subtitle' => $this->getTranslation('subtitle', $locale),
            'abstract_text' => $this->getTranslation('abstract_text', $locale),
            'description' => $this->getTranslation('description', $locale),
            'status' => $this->status,

            'start_date' => $this->start_date,
            'start_hour' => $this->start_hour,
            'end_date' => $this->end_date,
            'end_hour' => $this->end_hour,

            'location' => $this->getTranslation('location', $locale),

            'video_urls' => $videoUrlsForApi,
            'video_url' => $videoUrlsForApi[0] ?? null,

            'attachments' => $attachmentsPayload,

            'media' => [
                'cover_image' => CuratorMediaApiSerializer::serialize($cover, $coverAlt, $locale, $coverCaptionTranslations),
                'opengraph_picture' => CuratorMediaApiSerializer::serialize($og, $ogAlt, $locale, null),
                'gallery' => $gallery,
            ],

            'categories' => $categories->map(function (Category $cat) use ($locale) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->getTranslation('name', $locale),
                    'slug' => $cat->getTranslation('slug', $locale),
                    'type' => $cat->type ?? null,
                ];
            })->values(),

            // gruppi people (label + persone)
            'people_groups' => $peopleGroupsArray,

            // lista flat institutions
            'institutions' => $institutions->map(function (Institution $inst) use ($locale) {
                return [
                    'id' => $inst->id,
                    'name' => $inst->getTranslation('name', $locale),
                    'slug' => $inst->getTranslation('slug', $locale),
                    'status' => $inst->status,
                ];
            })->values(),

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
                'slug' => $this->getTranslations('slug'),
                'subtitle' => $this->getTranslations('subtitle'),
                'abstract_text' => $this->getTranslations('abstract_text'),
                'description' => $this->getTranslations('description'),
                'location' => $this->getTranslations('location'),
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

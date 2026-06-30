<?php

namespace App\Http\Resources;

use App\Models\Institution;
use App\Services\PeoplePageRoleCatalog;
use App\Support\EditorialHtmlSanitizer;
use App\Support\HttpExternalUrl;
use App\Support\PersonDetailPageResolver;
use Awcodes\Curator\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $request->query('locale', app()->getLocale());
        $light = $request->boolean('light');

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

        $institutionRoles = is_array($this->institution_roles) ? $this->institution_roles : [];

        $allInstitutionIds = collect($institutionRoles)
            ->pluck('institution_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $institutionsById = count($allInstitutionIds)
            ? Institution::whereIn('id', $allInstitutionIds)->get()->keyBy('id')
            : collect();

        $institutionsArray = collect($institutionRoles)->map(function ($row) use ($locale, $institutionsById) {
            if (! is_array($row)) {
                return null;
            }

            $institutionId = $row['institution_id'] ?? null;
            if ($institutionId === null || $institutionId === '') {
                return null;
            }

            $inst = $institutionsById->get((int) $institutionId);
            if (! $inst) {
                return null;
            }

            $roleTranslations = is_array($row['role'] ?? null) ? $row['role'] : [];
            $role = $roleTranslations[$locale] ?? (reset($roleTranslations) ?: null);
            $classified = PeoplePageRoleCatalog::classifyInstitutionRoleTranslations($roleTranslations);

            return [
                'id' => $inst->id,
                'name' => $inst->getTranslation('name', $locale),
                'slug' => $inst->getTranslation('slug', $locale),
                'status' => $inst->status,

                'role' => $role,
                'role_translations' => $roleTranslations,
                'role_key' => $classified['role_key'],
                'role_label_en' => $classified['role_label_en'],
            ];
        })->filter()->values();

        $sanitizer = app(EditorialHtmlSanitizer::class);
        $shortbioRaw = $light ? null : $this->getTranslation('shortbio', $locale);
        $shortbio = is_string($shortbioRaw) ? $sanitizer->sanitize($shortbioRaw) : null;

        return [
            'id' => $this->id,
            'first_name' => $this->getTranslation('first_name', $locale),
            'last_name' => $this->getTranslation('last_name', $locale),
            'slug' => $this->getTranslation('slug', $locale),
            'status' => $this->status,

            'role' => $this->getTranslation('role', $locale),
            'people_role_id' => $this->people_role_id,
            'people_role' => $this->whenLoaded('peopleRole', function () {
                $r = $this->peopleRole;

                return [
                    'id' => $r->id,
                    'slug' => $r->slug,
                    'name_en' => $r->name_en,
                    'name_it' => $r->name_it,
                ];
            }),
            'has_detail_page' => PersonDetailPageResolver::hasDetailPage($this->resource, $locale),
            'shortbio' => $shortbio,

            'email' => $light ? null : $this->email,
            'website' => $light ? null : HttpExternalUrl::normalizeForOutput($this->website),

            // IDs (retrocompatibilità)
            'image_id' => $this->image_id,
            'opengraph_picture_id' => $this->opengraph_picture_id,

            'media' => [
                'image' => $serializeMedia(
                    $this->relationLoaded('image') ? $this->image : null,
                    $this->getTranslation('image_alt', $locale) ?: null
                ),

                'opengraph_picture' => $serializeMedia(
                    $this->relationLoaded('opengraphPicture') ? $this->opengraphPicture : null,
                    $this->getTranslation('opengraph_picture_alt', $locale) ?: null
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

            'institutions' => $institutionsArray,
            'institution_roles' => $institutionRoles,

            'meta' => [
                'title' => $this->getTranslation('meta_title', $locale),
                'description' => $light ? null : $this->getTranslation('meta_description', $locale),
                'opengraph_title' => $this->getTranslation('opengraph_title', $locale),
                'opengraph_description' => $this->getTranslation('opengraph_description', $locale),
                'opengraph_picture_alt' => $this->getTranslation('opengraph_picture_alt', $locale),
            ],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'translations' => array_filter([
                'first_name' => $this->getTranslations('first_name'),
                'last_name' => $this->getTranslations('last_name'),
                'slug' => $this->getTranslations('slug'),
                'role' => $this->getTranslations('role'),
                'shortbio' => $light ? null : $this->getTranslations('shortbio'),
                'meta_title' => $this->getTranslations('meta_title'),
                'meta_description' => $light ? null : $this->getTranslations('meta_description'),
                'opengraph_title' => $this->getTranslations('opengraph_title'),
                'opengraph_description' => $this->getTranslations('opengraph_description'),
                'opengraph_picture_alt' => $this->getTranslations('opengraph_picture_alt'),
                'image_alt' => $this->getTranslations('image_alt'),
            ], fn ($v) => $v !== null),
        ];
    }
}

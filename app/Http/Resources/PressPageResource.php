<?php

namespace App\Http\Resources;

use App\Support\CuratorMediaApiSerializer;
use App\Support\PressDestinationPresenter;
use Illuminate\Http\Resources\Json\JsonResource;

class PressPageResource extends JsonResource
{
    public function toArray($request): array
    {
        $publishedContacts = $this->relationLoaded('contacts')
            ? $this->contacts
            : $this->contacts()->where('status', 'published')->orderBy('sort_order')->orderBy('id')->get();

        $publishedReleases = $this->relationLoaded('releases')
            ? $this->releases
            : $this->releases()->where('status', 'published')->orderBy('sort_order')->orderBy('id')->get();

        $publishedDocuments = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->where('status', 'published')->orderBy('sort_order')->orderBy('id')->get();

        return [
            'title' => $this->title,
            'intro' => $this->intro,
            'seo' => [
                'title' => $this->meta_title ?: $this->title,
                'description' => $this->meta_description,
            ],
            'contacts' => $publishedContacts->map(fn ($contact) => [
                'id' => (string) $contact->id,
                'label' => $contact->label,
                'email' => $contact->email,
            ])->values()->all(),
            'pressReleases' => $publishedReleases->map(function ($release) {
                $cover = $release->relationLoaded('coverImage')
                    ? $release->coverImage
                    : $release->coverImage()->first();

                return [
                    'id' => (string) $release->id,
                    'title' => $release->title,
                    'description' => $release->description,
                    'cover' => CuratorMediaApiSerializer::serialize(
                        $cover,
                        $release->cover_image_alt,
                        'en',
                    ),
                    'destination' => PressDestinationPresenter::forApi(
                        $release->destination_type,
                        $release->file_path,
                        $release->external_url,
                    ),
                ];
            })->values()->all(),
            'documents' => $publishedDocuments->map(fn ($document) => [
                'id' => (string) $document->id,
                'category' => $document->category,
                'title' => $document->title,
                'date' => $document->date?->format('Y-m-d'),
                'destination' => PressDestinationPresenter::forApi(
                    $document->destination_type,
                    $document->file_path,
                    $document->external_url,
                ),
            ])->values()->all(),
        ];
    }
}

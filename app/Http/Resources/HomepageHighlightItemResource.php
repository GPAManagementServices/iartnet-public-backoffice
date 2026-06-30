<?php

namespace App\Http\Resources;

use App\Models\HomepageHighlightItem;
use App\Support\CuratorMediaApiSerializer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageHighlightItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = $this->relationLoaded('coverMedia')
            ? $this->coverMedia
            : $this->coverMedia()->first();

        return [
            'id' => (string) $this->id,
            'title_variant' => $this->title_variant,
            'title' => $this->titlePayload(),
            'description' => $this->description,
            'digital_object_slug' => $this->digital_object_slug,
            'link' => '/digital-object/'.$this->digital_object_slug,
            'media' => CuratorMediaApiSerializer::serialize($media, null, (string) $request->query('locale', app()->getLocale()), null),
            'cover_iiif_identifier' => $this->cover_iiif_identifier,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function titlePayload(): array
    {
        if ($this->title_variant === HomepageHighlightItem::TITLE_VARIANT_TITLE_SUBTITLE1_SUBTITLE2) {
            return [
                'titolo' => $this->title,
                'sottotitolo1' => $this->subtitle_1,
                'sottotitolo2' => $this->subtitle_2,
            ];
        }

        return [
            'autore' => $this->author,
            'titolo' => $this->title,
            'sottotitolo' => $this->subtitle_1,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Support\CuratorMediaApiSerializer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeroCarouselItemResource extends JsonResource
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
            'title' => $this->title,
            'digital_object_slug' => $this->digital_object_slug,
            'slug' => '/digital-object/'.$this->digital_object_slug,
            'media' => CuratorMediaApiSerializer::serialize($media, null, (string) $request->query('locale', app()->getLocale()), null),
        ];
    }
}

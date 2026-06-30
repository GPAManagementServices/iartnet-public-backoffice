<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroCarouselItem extends Model
{
    protected $fillable = [
        'title',
        'digital_object_slug',
        'cover_media_id',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function scopePublishedForHomepage(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('cover_media_id')
            ->where('title', '<>', '')
            ->where('digital_object_slug', '<>', '');
    }

    public function scopeOrderedForHomepage(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}

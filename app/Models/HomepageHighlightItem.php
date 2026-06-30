<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageHighlightItem extends Model
{
    public const TITLE_VARIANT_AUTHOR_TITLE_SUBTITLE = 'author_title_subtitle';

    public const TITLE_VARIANT_TITLE_SUBTITLE1_SUBTITLE2 = 'title_subtitle1_subtitle2';

    protected $fillable = [
        'title_variant',
        'title',
        'author',
        'subtitle_1',
        'subtitle_2',
        'description',
        'digital_object_slug',
        'cover_media_id',
        'cover_iiif_identifier',
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
            ->where('title', '<>', '')
            ->where('digital_object_slug', '<>', '')
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('cover_media_id')
                    ->orWhere(function (Builder $query) {
                        $query
                            ->whereNotNull('cover_iiif_identifier')
                            ->where('cover_iiif_identifier', '<>', '');
                    });
            });
    }

    public function scopeOrderedForHomepage(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}

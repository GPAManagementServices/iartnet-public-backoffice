<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ResearchCatalogue extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasTranslations;

    protected $table = 'research_catalogues';

    protected $fillable = [
        'created_by',
        'updated_by',
        'title',
        'status',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'opengraph_picture_alt',
        'description',
        'cover_image_id',
        'external_link',
        'author',
        'slug_it',
        'slug_en',
        'cover_image_alt',
        'cover_image_caption',
    ];

    public array $translatable = [
        'title',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_alt',
        'description',
        'cover_image_alt',
        'cover_image_caption',
    ];

    protected $casts = [
        'title' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'opengraph_title' => 'array',
        'opengraph_description' => 'array',
        'opengraph_picture_alt' => 'array',
        'description' => 'array',
        'cover_image_alt' => 'array',
        'cover_image_caption' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (Auth::check()) {
                $model->created_by ??= Auth::id();
                $model->updated_by ??= Auth::id();
            }
        });

        static::updating(function (self $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // Auto-slug (se vuoti) + normalizzazione
        static::saving(function (self $model) {
            $model->slug_en = Str::slug((string) ($model->slug_en ?: $model->getTranslation('title', 'en')));
            $model->slug_it = Str::slug((string) ($model->slug_it ?: $model->getTranslation('title', 'it')));
        });
    }

    public function opengraphPicture(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'opengraph_picture_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->where('type', Category::TYPE_RESEARCH_CATALOGUE)
            ->withTimestamps();
    }

    protected function scalarOptionalTextAttributes(): array
    {
        return ['external_link', 'author'];
    }

    protected function scalarRequiredTextAttributes(): array
    {
        return ['slug_it', 'slug_en', 'status'];
    }
}

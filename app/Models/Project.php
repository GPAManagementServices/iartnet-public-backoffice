<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Support\ProjectEditorialPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Project extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'created_by',
        'updated_by',
        'title',
        'subtitle',
        'slug',
        'slug_it',
        'slug_en',
        'status',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'opengraph_picture_alt',
        'description',
        'cover_image_id',
        'cover_image_alt',
        'cover_image_caption',
        'people',
        'institutions',
        'gallery',
        'show_in_homepage',
        'homepage_order',
        'show_in_projects',
        'projects_order',
    ];

    public array $translatable = [
        'title',
        'subtitle',
        'slug',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_alt',
        'cover_image_alt',
        'cover_image_caption',
        'description',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'slug' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'opengraph_title' => 'array',
        'opengraph_description' => 'array',
        'opengraph_picture_alt' => 'array',
        'cover_image_alt' => 'array',
        'cover_image_caption' => 'array',
        'description' => 'array',
        'gallery' => 'array',
        'people' => 'array',
        'institutions' => 'array',
        'show_in_homepage' => 'boolean',
        'homepage_order' => 'integer',
        'show_in_projects' => 'boolean',
        'projects_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (Auth::check()) {
                $model->created_by ??= Auth::id();
                $model->updated_by ??= Auth::id();
            }

            if (blank($model->slug)) {
                $title = is_array($model->title)
                    ? ($model->title[app()->getLocale()] ?? reset($model->title) ?? '')
                    : (string) $model->title;

                $model->slug = Str::slug($title);
            }
        });

        static::updating(function (self $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::saving(function (self $model) {
            $it = (string) $model->getTranslation('slug', 'it');
            $en = (string) $model->getTranslation('slug', 'en');

            $model->slug_it = $it !== '' ? Str::slug($it) : null;
            $model->slug_en = $en !== '' ? Str::slug($en) : null;

            ProjectEditorialPosition::applyNormalization($model);
        });

        static::saved(function (self $model) {
            ProjectEditorialPosition::dissociateConflictingPositions($model);
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
            ->where('type', Category::TYPE_PROJECT)
            ->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeVisibleInHomepage(Builder $query): Builder
    {
        return $query->where('show_in_homepage', true);
    }

    public function scopeVisibleInProjects(Builder $query): Builder
    {
        return $query->where('show_in_projects', true);
    }

    public function scopeOrderedForHomepage(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN homepage_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('homepage_order')
            ->orderByDesc('updated_at');
    }

    public function scopeOrderedForProjects(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN projects_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('projects_order')
            ->orderByDesc('updated_at');
    }

    public function institutionsModels()
    {
        $ids = is_array($this->institutions) ? $this->institutions : [];

        if (empty($ids)) {
            return collect();
        }

        return Institution::whereIn('id', $ids)->get();
    }

    public function peopleModels()
    {
        $groups = is_array($this->people) ? $this->people : [];

        $ids = collect($groups)
            ->pluck('people_ids')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return Person::whereIn('id', $ids)->get();
    }

    protected function jsonArrayTextNormalizableAttributes(): array
    {
        return ['people', 'gallery'];
    }
}

<?php

namespace App\Models;

use App\Support\ActivityVideoUrls;
use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Activity extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasFactory;
    use HasTranslations;

    protected $table = 'activities';

    public array $translatable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_alt',
        'subtitle',
        'abstract_text',
        'description',
        'location',
        'cover_image_alt',
        'cover_image_caption',
    ];

    protected $fillable = [
        'created_by',
        'updated_by',
        'title',
        'slug',
        'status',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'opengraph_picture_alt',
        'subtitle',
        'abstract_text',
        'institutions',
        'description',
        'cover_image_id',
        'people',
        'gallery',
        'video_url',
        'video_urls',
        'attachments',
        'start_date',
        'start_hour',
        'end_date',
        'end_hour',
        'location',
        'cover_image_alt',
        'cover_image_caption',
    ];

    protected $casts = [
        'title' => 'array',
        'slug' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'opengraph_title' => 'array',
        'opengraph_description' => 'array',
        'opengraph_picture_alt' => 'array',
        'subtitle' => 'array',
        'abstract_text' => 'array',
        'description' => 'array',

        'institutions' => 'array',
        'people' => 'array',
        'gallery' => 'array',
        'video_urls' => 'array',
        'attachments' => 'array',

        'location' => 'array',

        'start_date' => 'date',
        'end_date' => 'date',

        'cover_image_alt' => 'array',
        'cover_image_caption' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            if (Auth::check()) {
                $activity->created_by = Auth::id();
                $activity->updated_by = Auth::id();
            }
        });

        static::updating(function (self $activity) {
            if (Auth::check()) {
                $activity->updated_by = Auth::id();
            }
        });

        static::saving(function (self $activity) {
            $it = (string) $activity->getTranslation('slug', 'it');
            $en = (string) $activity->getTranslation('slug', 'en');

            $activity->slug_it = $it !== '' ? Str::slug($it) : null;
            $activity->slug_en = $en !== '' ? Str::slug($en) : null;
        });

        static::saving(function (self $activity) {
            $activity->applyVideoUrlsNormalizationAndLegacySync();
        });
    }

    /**
     * Normalizza `video_urls`, deduplica, e sincronizza la colonna legacy `video_url` con il primo elemento.
     */
    public function applyVideoUrlsNormalizationAndLegacySync(): void
    {
        $normalized = ActivityVideoUrls::normalizeForStorage($this->video_urls);

        $this->setAttribute('video_urls', $normalized);
        $this->setAttribute('video_url', ActivityVideoUrls::firstOrNull($normalized));
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function coverImage()
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    public function opengraphPicture()
    {
        return $this->belongsTo(Media::class, 'opengraph_picture_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->where('type', Category::TYPE_ACTIVITY)
            ->withTimestamps();
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

    protected function scalarOptionalTextAttributes(): array
    {
        return [];
    }

    protected function jsonArrayTextNormalizableAttributes(): array
    {
        return ['people', 'attachments', 'gallery'];
    }
}

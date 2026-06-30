<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Institution extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasFactory;
    use HasTranslations;

    protected $table = 'institutions';

    public array $translatable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_alt',
        'description',
        'logo_image_alt',
        'cover_image_alt',
    ];

    protected $fillable = [
        'created_by',
        'updated_by',
        'name',
        'slug',
        'website',
        'status',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'opengraph_picture_alt',
        'description',
        'people',
        'logo_image_id',
        'cover_image_id',
        'logo_image_alt',
        'cover_image_alt',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'opengraph_title' => 'array',
        'opengraph_description' => 'array',
        'opengraph_picture_alt' => 'array',
        'description' => 'array',
        'people' => 'array',
        'logo_image_alt' => 'array',
        'cover_image_alt' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $institution) {
            if (Auth::check()) {
                $institution->created_by = Auth::id();
                $institution->updated_by = Auth::id();
            }
        });

        static::updating(function (self $institution) {
            if (Auth::check()) {
                $institution->updated_by = Auth::id();
            }
        });

        static::saving(function (self $model) {
            $it = (string) $model->getTranslation('slug', 'it');
            $en = (string) $model->getTranslation('slug', 'en');

            $model->slug_it = $it !== '' ? Str::slug($it) : null;
            $model->slug_en = $en !== '' ? Str::slug($en) : null;
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logoImage()
    {
        return $this->belongsTo(Media::class, 'logo_image_id');
    }

    public function opengraphPicture()
    {
        return $this->belongsTo(Media::class, 'opengraph_picture_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->where('type', Category::TYPE_INSTITUTION)
            ->withTimestamps();
    }

    public function peopleModels()
    {
        $ids = is_array($this->people) ? $this->people : [];

        if (empty($ids)) {
            return collect();
        }

        return Person::whereIn('id', $ids)->get();
    }

    public function coverImage()
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    protected function scalarOptionalTextAttributes(): array
    {
        return ['website'];
    }
}

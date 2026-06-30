<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasTranslations;

    protected $table = 'categories';

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_INSTITUTION = 'institution';

    public const TYPE_PERSON = 'person';

    public const TYPE_RESEARCH_CATALOGUE = 'research_catalogue';

    public const TYPE_PROJECT = 'project';

    protected $fillable = [
        'type',
        'name',
        'slug',
        'status',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by',
    ];

    public array $translatable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
    ];

    /* -----------------------------------------------------------------
     | Boot: created_by / updated_by
     |----------------------------------------------------------------- */

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (Auth::check()) {
                $category->created_by = Auth::id();
                $category->updated_by = Auth::id();
            }
        });

        static::updating(function (Category $category) {
            if (Auth::check()) {
                $category->updated_by = Auth::id();
            }
        });

        static::saving(function (self $model) {
            $it = (string) $model->getTranslation('slug', 'it');
            $en = (string) $model->getTranslation('slug', 'en');

            $model->slug_it = $it !== '' ? Str::slug($it) : null;
            $model->slug_en = $en !== '' ? Str::slug($en) : null;
        });
    }

    /* -----------------------------------------------------------------
     | Scopes
     |----------------------------------------------------------------- */

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /* -----------------------------------------------------------------
     | Polymorphic relations (categorizables)
     |----------------------------------------------------------------- */

    public function activities()
    {
        return $this->morphedByMany(Activity::class, 'categorizable', 'categorizables');
    }

    public function institutions()
    {
        return $this->morphedByMany(Institution::class, 'categorizable', 'categorizables');
    }

    public function people()
    {
        return $this->morphedByMany(Person::class, 'categorizable', 'categorizables');
    }

    public function researchCatalogues()
    {
        return $this->morphedByMany(ResearchCatalogue::class, 'categorizable', 'categorizables');
    }

    public function project()
    {
        return $this->morphedByMany(Project::class, 'categorizable', 'categorizables');
    }

    protected function scalarRequiredTextAttributes(): array
    {
        return ['type', 'status'];
    }
}

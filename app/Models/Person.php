<?php

namespace App\Models;

use App\Support\EditorialHtmlSanitizer;
use App\Support\HttpExternalUrl;
use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Person extends Model
{
    use Concerns\NormalizesTextOnSave;
    use HasFactory;
    use HasTranslations;

    protected $table = 'people';

    public array $translatable = [
        'first_name',
        'last_name',
        'slug',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_alt',
        'role',
        'shortbio',
        'image_alt',
    ];

    protected $fillable = [
        'created_by',
        'updated_by',
        'first_name',
        'last_name',
        'slug',
        'status',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'opengraph_picture_alt',
        'role',
        'image_id',
        'shortbio',
        'institutions',
        'institution_roles',
        'people_role_id',
        'email',
        'website',
        'image_alt',
    ];

    protected $casts = [
        'first_name' => 'array',
        'last_name' => 'array',
        'slug' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'opengraph_title' => 'array',
        'opengraph_description' => 'array',
        'opengraph_picture_alt' => 'array',
        'role' => 'array',
        'shortbio' => 'array',

        'institutions' => 'array',
        'institution_roles' => 'array',
        'image_alt' => 'array',
        'people_role_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $person) {
            if (Auth::check()) {
                $person->created_by = Auth::id();
                $person->updated_by = Auth::id();
            }
        });

        static::updating(function (self $person) {
            if (Auth::check()) {
                $person->updated_by = Auth::id();
            }
        });

        static::saving(function (self $model) {

            $it = (string) $model->getTranslation('slug', 'it');
            $en = (string) $model->getTranslation('slug', 'en');

            $model->slug_it = $it !== '' ? Str::slug($it) : null;
            $model->slug_en = $en !== '' ? Str::slug($en) : null;

            if ($model->people_role_id) {
                $catalogRole = PeopleRole::query()->find($model->people_role_id);
                if ($catalogRole) {
                    $model->setTranslation('role', 'en', $catalogRole->name_en);
                    $model->setTranslation('role', 'it', $catalogRole->name_it);
                }
            }
            // Se people_role_id è null: non sovrascrivere role (dati legacy / solo JSON)

            $rows = is_array($model->institution_roles) ? $model->institution_roles : [];
            $newRows = [];

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $institutionId = $row['institution_id'] ?? null;
                if ($institutionId === null || $institutionId === '') {
                    continue;
                }

                $prId = $row['people_role_id'] ?? null;
                $prId = $prId !== null && $prId !== '' ? (int) $prId : null;

                if ($prId) {
                    $catalog = PeopleRole::query()->find($prId);
                    if ($catalog) {
                        $newRows[] = [
                            'institution_id' => (string) $institutionId,
                            'people_role_id' => $prId,
                            'role' => [
                                'en' => $catalog->name_en,
                                'it' => $catalog->name_it,
                            ],
                        ];

                        continue;
                    }
                }

                $legacyRole = is_array($row['role'] ?? null) ? $row['role'] : [];

                $newRows[] = [
                    'institution_id' => (string) $institutionId,
                    'people_role_id' => null,
                    'role' => [
                        'en' => $legacyRole['en'] ?? null,
                        'it' => $legacyRole['it'] ?? null,
                    ],
                ];
            }

            $model->institution_roles = array_values($newRows);

            $ids = collect($newRows)
                ->map(fn ($row) => $row['institution_id'] ?? null)
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $model->institutions = $ids;
        });

        static::saving(function (self $person) {
            $sanitizer = app(EditorialHtmlSanitizer::class);

            foreach ($person->getTranslations('shortbio') as $locale => $value) {
                if (is_string($value)) {
                    $person->setTranslation('shortbio', $locale, $sanitizer->sanitize($value) ?? '');
                }
            }

            if (array_key_exists('website', $person->getAttributes())) {
                $person->website = HttpExternalUrl::normalizeForStorage(
                    is_string($person->website) ? $person->website : null
                );
            }
        });
    }

    public function peopleRole()
    {
        return $this->belongsTo(PeopleRole::class, 'people_role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function image()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function opengraphPicture()
    {
        return $this->belongsTo(Media::class, 'opengraph_picture_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->where('type', Category::TYPE_PERSON)
            ->withTimestamps();
    }

    public function institutionRoles(): array
    {
        $rows = is_array($this->institution_roles) ? $this->institution_roles : [];

        return array_values(array_filter($rows, function ($row) {
            return is_array($row)
                && isset($row['institution_id'])
                && $row['institution_id'] !== null
                && $row['institution_id'] !== '';
        }));
    }

    public function institutionsModels()
    {
        $ids = collect($this->institutionRoles())
            ->map(fn ($row) => (string) $row['institution_id'])
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return Institution::whereIn('id', $ids)->get();
    }

    protected function scalarOptionalTextAttributes(): array
    {
        return ['email', 'website'];
    }

    protected function jsonArrayTextNormalizableAttributes(): array
    {
        return ['institution_roles'];
    }
}

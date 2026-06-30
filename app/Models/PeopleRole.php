<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeopleRole extends Model
{
    protected $fillable = [
        'slug',
        'name_en',
        'name_it',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'people_role_id');
    }

    public function isInUse(): bool
    {
        if ($this->people()->exists()) {
            return true;
        }

        $id = (int) $this->id;

        return Person::query()->where(function ($q) use ($id) {
            $q->where('institution_roles', 'like', '%"people_role_id":'.$id.'%')
                ->orWhere('institution_roles', 'like', '%"people_role_id":"'.$id.'"%');
        })->exists();
    }

    public function labelForLocale(string $locale): string
    {
        return $locale === 'it' ? (string) $this->name_it : (string) $this->name_en;
    }
}

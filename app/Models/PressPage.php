<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PressPage extends Model
{
    public const SINGLETON_KEY = 'default';

    protected $fillable = [
        'singleton_key',
        'status',
        'title',
        'intro',
        'meta_title',
        'meta_description',
        'opengraph_title',
        'opengraph_description',
        'opengraph_picture_id',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->singleton_key ??= self::SINGLETON_KEY;
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
    }

    public function opengraphPicture(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'opengraph_picture_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PressContact::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(PressRelease::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PressDocument::class);
    }

    public static function singletonOrNull(): ?self
    {
        return static::query()
            ->where('singleton_key', self::SINGLETON_KEY)
            ->first();
    }

    public static function resolveSingleton(): self
    {
        return static::query()->firstOrCreate(
            ['singleton_key' => self::SINGLETON_KEY],
            [
                'status' => 'draft',
                'title' => 'Press area',
            ],
        );
    }
}

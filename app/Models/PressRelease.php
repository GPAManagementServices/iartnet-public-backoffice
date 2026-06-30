<?php

namespace App\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PressRelease extends Model
{
    protected $fillable = [
        'press_page_id',
        'title',
        'description',
        'cover_image_id',
        'cover_image_alt',
        'destination_type',
        'file_path',
        'external_url',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            self::normalizeDestination($model);
        });
    }

    public function pressPage(): BelongsTo
    {
        return $this->belongsTo(PressPage::class);
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    private static function normalizeDestination(self $model): void
    {
        $type = is_string($model->destination_type) ? $model->destination_type : 'none';

        if ($type === 'file') {
            $model->external_url = null;

            return;
        }

        if ($type === 'external') {
            $model->file_path = null;

            return;
        }

        $model->destination_type = 'none';
        $model->file_path = null;
        $model->external_url = null;
    }
}

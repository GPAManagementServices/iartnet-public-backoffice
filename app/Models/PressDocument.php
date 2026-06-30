<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PressDocument extends Model
{
    protected $fillable = [
        'press_page_id',
        'category',
        'title',
        'date',
        'destination_type',
        'file_path',
        'external_url',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'date' => 'date:Y-m-d',
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

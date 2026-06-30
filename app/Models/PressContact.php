<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PressContact extends Model
{
    protected $fillable = [
        'press_page_id',
        'label',
        'email',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function pressPage(): BelongsTo
    {
        return $this->belongsTo(PressPage::class);
    }
}

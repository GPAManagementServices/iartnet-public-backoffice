<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Support\ActivityVideoUrls;
use Filament\Resources\Pages\CreateRecord;

class CreateActivity extends CreateRecord
{
    protected static string $resource = ActivityResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['video_urls'] = ActivityVideoUrls::normalizeRepeaterFormState($data['video_urls'] ?? null);

        return $data;
    }
}

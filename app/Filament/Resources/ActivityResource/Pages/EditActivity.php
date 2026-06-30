<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Support\ActivityVideoUrls;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['video_urls'] = ActivityVideoUrls::normalizeRepeaterFormState($data['video_urls'] ?? null);

        return $data;
    }

    public function getFormColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

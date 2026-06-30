<?php

namespace App\Filament\Resources\HomepageHighlightItemResource\Pages;

use App\Filament\Resources\HomepageHighlightItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomepageHighlightItems extends ListRecords
{
    protected static string $resource = HomepageHighlightItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

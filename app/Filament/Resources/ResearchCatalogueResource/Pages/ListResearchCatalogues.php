<?php

namespace App\Filament\Resources\ResearchCatalogueResource\Pages;

use App\Filament\Resources\ResearchCatalogueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResearchCatalogues extends ListRecords
{
    protected static string $resource = ResearchCatalogueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

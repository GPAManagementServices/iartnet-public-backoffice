<?php

namespace App\Filament\Resources\ResearchCatalogueResource\Pages;

use App\Filament\Resources\ResearchCatalogueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditResearchCatalogue extends EditRecord
{
    protected static string $resource = ResearchCatalogueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

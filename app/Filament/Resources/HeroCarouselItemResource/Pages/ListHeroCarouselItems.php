<?php

namespace App\Filament\Resources\HeroCarouselItemResource\Pages;

use App\Filament\Resources\HeroCarouselItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHeroCarouselItems extends ListRecords
{
    protected static string $resource = HeroCarouselItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

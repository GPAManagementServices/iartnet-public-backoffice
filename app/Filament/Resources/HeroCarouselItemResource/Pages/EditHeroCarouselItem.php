<?php

namespace App\Filament\Resources\HeroCarouselItemResource\Pages;

use App\Filament\Resources\HeroCarouselItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHeroCarouselItem extends EditRecord
{
    protected static string $resource = HeroCarouselItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

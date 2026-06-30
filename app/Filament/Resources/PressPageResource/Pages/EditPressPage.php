<?php

namespace App\Filament\Resources\PressPageResource\Pages;

use App\Filament\Resources\PressPageResource;
use Filament\Resources\Pages\EditRecord;

class EditPressPage extends EditRecord
{
    protected static string $resource = PressPageResource::class;

    protected static ?string $title = 'Press';

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            PressPageResource::getNavigationLabel(),
        ];
    }

    /**
     * @return array<\Filament\Actions\Action | \Filament\Actions\ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

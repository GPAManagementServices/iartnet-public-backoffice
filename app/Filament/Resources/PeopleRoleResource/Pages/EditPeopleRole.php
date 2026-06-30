<?php

namespace App\Filament\Resources\PeopleRoleResource\Pages;

use App\Filament\Resources\PeopleRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPeopleRole extends EditRecord
{
    protected static string $resource = PeopleRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(fn () => $this->record->isInUse()),
        ];
    }
}

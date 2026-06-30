<?php

namespace App\Filament\Resources\PeopleRoleResource\Pages;

use App\Filament\Resources\PeopleRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeopleRoles extends ListRecords
{
    protected static string $resource = PeopleRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PersonResource\Pages;

use App\Filament\Resources\PersonResource;
use App\Models\PeopleRole;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (empty($data['people_role_id'] ?? null)) {
            $record = $this->record;
            $en = trim((string) ($record->getTranslation('role', 'en') ?? ''));
            $it = trim((string) ($record->getTranslation('role', 'it') ?? ''));
            $match = null;
            if ($en !== '') {
                $match = PeopleRole::query()->where('name_en', $en)->first();
            }
            if (! $match && $it !== '') {
                $match = PeopleRole::query()->where('name_it', $it)->first();
            }
            if ($match) {
                $data['people_role_id'] = $match->id;
            }
        }

        $rows = $data['institution_roles'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (! empty($row['people_role_id'] ?? null)) {
                    continue;
                }
                $rEn = trim((string) (($row['role']['en'] ?? '') ?? ''));
                if ($rEn !== '') {
                    $pr = PeopleRole::query()->where('name_en', $rEn)->first();
                    if ($pr) {
                        $data['institution_roles'][$i]['people_role_id'] = $pr->id;
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Non presente nel form: evitare che valori disallineati sovrascrivano il sync da catalogo in saving().
        unset($data['role']);

        return $data;
    }
}

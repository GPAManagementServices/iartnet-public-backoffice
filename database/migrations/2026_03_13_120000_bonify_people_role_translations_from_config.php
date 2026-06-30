<?php

use App\Models\Person;
use Illuminate\Database\Migrations\Migration;

/**
 * Allinea traduzioni mancanti su people.role e people.institution_roles
 * usando le coppie EN/IT definite in config/people_page.php (incluso research_group_coordinator).
 *
 * Non reversibile: down() è vuoto.
 */
return new class extends Migration
{
    public function up(): void
    {
        [$enToIt, $itToEn] = self::buildTranslationMaps();

        Person::query()->orderBy('id')->chunkById(100, function ($people) use ($enToIt, $itToEn) {
            foreach ($people as $person) {
                $dirty = false;

                $role = $person->role;
                if (is_array($role)) {
                    [$role, $changed] = self::fillMissingTranslations($role, $enToIt, $itToEn);
                    $dirty = $dirty || $changed;
                }

                $institutionRoles = $person->institution_roles;
                if (is_array($institutionRoles)) {
                    foreach ($institutionRoles as $i => $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $sub = $row['role'] ?? null;
                        if (! is_array($sub)) {
                            continue;
                        }
                        [$sub, $changed] = self::fillMissingTranslations($sub, $enToIt, $itToEn);
                        if ($changed) {
                            $institutionRoles[$i]['role'] = $sub;
                            $dirty = true;
                        }
                    }
                }

                if ($dirty) {
                    if (is_array($role)) {
                        $person->role = $role;
                    }
                    if (is_array($institutionRoles)) {
                        $person->institution_roles = $institutionRoles;
                    }
                    $person->save();
                }
            }
        });
    }

    public function down(): void
    {
        // Bonifica dati non reversibile automaticamente.
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private static function buildTranslationMaps(): array
    {
        $maps = array_merge(
            config('people_page.global_roles', []),
            config('people_page.dedicated_section_roles', []),
            config('people_page.institution_section_roles', []),
        );

        $enToIt = [];
        $itToEn = [];

        foreach ($maps as $labels) {
            if (! is_array($labels)) {
                continue;
            }
            $en = trim((string) ($labels['en'] ?? ''));
            $it = trim((string) ($labels['it'] ?? ''));
            if ($en === '' || $it === '') {
                continue;
            }
            $enToIt[strtolower($en)] = $it;
            $itToEn[mb_strtolower($it, 'UTF-8')] = $en;
        }

        return [$enToIt, $itToEn];
    }

    /**
     * @param  array<string, mixed>  $role
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private static function fillMissingTranslations(array $role, array $enToIt, array $itToEn): array
    {
        $changed = false;

        $en = isset($role['en']) ? trim((string) $role['en']) : '';
        $it = isset($role['it']) ? trim((string) $role['it']) : '';

        $enEmpty = $en === '' || strtolower($en) === 'null';
        $itEmpty = $it === '' || strtolower($it) === 'null';

        if (! $enEmpty && $itEmpty) {
            $key = strtolower($en);
            if (isset($enToIt[$key])) {
                $role['it'] = $enToIt[$key];
                $changed = true;
            }
        }

        if (! $itEmpty && $enEmpty) {
            $key = mb_strtolower($it, 'UTF-8');
            if (isset($itToEn[$key])) {
                $role['en'] = $itToEn[$key];
                $changed = true;
            }
        }

        return [$role, $changed];
    }
};

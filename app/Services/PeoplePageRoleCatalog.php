<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

/**
 * Classificazione ruoli people/istituzioni allineata a config/people_page.php
 * (stessa logica concettuale di PeoplePageGroupedResponseBuilder::matchSectionRole).
 */
final class PeoplePageRoleCatalog
{
    /**
     * Chiavi ammesse per filtro query ?role= (escluso no_role: non filtrabile in SQL in modo affidabile).
     *
     * @return list<string>
     */
    public static function filterableRoleKeysForQuery(): array
    {
        $no = (string) config('people_page.no_role_section_slug', 'no_role');

        return array_values(array_filter(self::allConfiguredRoleKeys(), fn (string $k) => $k !== $no));
    }

    /**
     * Tutte le chiavi ruolo note (global + dedicated + institution sections + no_role).
     *
     * @return list<string>
     */
    public static function allConfiguredRoleKeys(): array
    {
        $keys = array_merge(
            array_keys(config('people_page.global_roles', [])),
            array_keys(config('people_page.dedicated_section_roles', [])),
            array_keys(config('people_page.institution_section_roles', [])),
            [(string) config('people_page.no_role_section_slug', 'no_role')],
        );

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>|null  $roleTranslations
     * @return array{role_key: string, role_label_en: ?string}
     */
    public static function classifyInstitutionRoleTranslations(?array $roleTranslations): array
    {
        $labels = self::collectTrimmedLabels($roleTranslations);
        $noRole = (string) config('people_page.no_role_section_slug', 'no_role');

        if ($labels === []) {
            return ['role_key' => $noRole, 'role_label_en' => null];
        }

        foreach ($labels as $label) {
            $key = self::matchInstitutionRoleLabel($label);
            if ($key !== null) {
                return [
                    'role_key' => $key,
                    'role_label_en' => self::labelEnForInstitutionKey($key),
                ];
            }
        }

        return ['role_key' => $noRole, 'role_label_en' => null];
    }

    /**
     * Etichette esatte (tutte le lingue in config) per match JSON_SEARCH su role / institution_roles.
     *
     * @return list<string>
     */
    public static function exactLabelsForRoleKey(string $roleKey): array
    {
        $labels = [];

        $global = config('people_page.global_roles', []);
        if (isset($global[$roleKey]) && is_array($global[$roleKey])) {
            self::pushLabelsFromMap($global[$roleKey], $labels);
        }

        $dedicated = config('people_page.dedicated_section_roles', []);
        if (isset($dedicated[$roleKey]) && is_array($dedicated[$roleKey])) {
            self::pushLabelsFromMap($dedicated[$roleKey], $labels);
        }

        $sections = config('people_page.institution_section_roles', []);
        if (isset($sections[$roleKey]) && is_array($sections[$roleKey])) {
            self::pushLabelsFromMap($sections[$roleKey], $labels);
        }

        return array_values(array_unique(array_filter($labels)));
    }

    public static function personMatchesRoleFilter(Person $person, string $roleKey): bool
    {
        if (in_array($roleKey, ['academic_coordinator', 'research_unit_lead'], true)) {
            if (self::personGlobalRoleMatchesKey($person, $roleKey)) {
                return true;
            }
        }

        if ($roleKey === 'academic_coordinator') {
            return false;
        }

        return self::personInstitutionRolesContainKey($person, $roleKey);
    }

    private static function personGlobalRoleMatchesKey(Person $person, string $roleKey): bool
    {
        $map = config('people_page.global_roles.'.$roleKey);
        if (! is_array($map)) {
            return false;
        }

        $role = $person->role;
        if (! is_array($role)) {
            return false;
        }

        foreach ($role as $v) {
            if (! is_string($v)) {
                continue;
            }
            $label = trim($v);
            if ($label === '' || strtolower($label) === 'null') {
                continue;
            }
            foreach ($map as $expected) {
                if (is_string($expected) && trim($expected) !== '' && $label === trim($expected)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function personInstitutionRolesContainKey(Person $person, string $roleKey): bool
    {
        $rows = is_array($person->institution_roles) ? $person->institution_roles : [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $translations = is_array($row['role'] ?? null) ? $row['role'] : [];
            $class = self::classifyInstitutionRoleTranslations($translations);
            if (($class['role_key'] ?? null) === $roleKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $roleTranslations
     * @return list<string>
     */
    private static function collectTrimmedLabels(?array $roleTranslations): array
    {
        if ($roleTranslations === null || $roleTranslations === []) {
            return [];
        }

        $out = [];
        foreach ($roleTranslations as $v) {
            if (! is_string($v)) {
                continue;
            }
            $t = trim($v);
            if ($t === '' || strtolower($t) === 'null') {
                continue;
            }
            $out[] = $t;
        }

        return array_values(array_unique($out));
    }

    private static function matchInstitutionRoleLabel(string $label): ?string
    {
        $rul = config('people_page.global_roles.research_unit_lead', []);
        if (self::labelInMap($label, $rul)) {
            return 'research_unit_lead';
        }

        foreach (config('people_page.dedicated_section_roles', []) as $slug => $map) {
            if (is_array($map) && self::labelInMap($label, $map)) {
                return (string) $slug;
            }
        }

        foreach (config('people_page.institution_section_roles', []) as $slug => $map) {
            if (is_array($map) && self::labelInMap($label, $map)) {
                return (string) $slug;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private static function labelInMap(string $label, array $map): bool
    {
        foreach ($map as $v) {
            if (is_string($v) && trim($v) !== '' && $label === trim($v)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  list<string>  $labels
     */
    private static function pushLabelsFromMap(array $map, array &$labels): void
    {
        foreach ($map as $v) {
            if (is_string($v) && trim($v) !== '') {
                $labels[] = trim($v);
            }
        }
    }

    private static function labelEnForInstitutionKey(string $roleKey): ?string
    {
        $no = (string) config('people_page.no_role_section_slug', 'no_role');
        if ($roleKey === $no) {
            return null;
        }

        $global = config('people_page.global_roles', []);
        if (isset($global[$roleKey]['en'])) {
            return (string) $global[$roleKey]['en'];
        }

        $dedicated = config('people_page.dedicated_section_roles', []);
        if (isset($dedicated[$roleKey]['en'])) {
            return (string) $dedicated[$roleKey]['en'];
        }

        $sections = config('people_page.institution_section_roles', []);
        if (isset($sections[$roleKey]['en'])) {
            return (string) $sections[$roleKey]['en'];
        }

        return null;
    }

    /**
     * Filtro SQL retrocompatibile su colonna JSON role / institution_roles (MySQL/MariaDB JSON_SEARCH).
     */
    public static function applyRoleFilterToPeopleQuery(Builder $query, string $roleKey): void
    {
        $labels = self::exactLabelsForRoleKey($roleKey);
        if ($labels === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($outer) use ($roleKey, $labels) {
            if ($roleKey === 'research_unit_lead') {
                $outer->where(function ($q) use ($labels) {
                    foreach ($labels as $l) {
                        $q->orWhereRaw('JSON_SEARCH(role, \'one\', ?) IS NOT NULL', [$l]);
                    }
                })->orWhere(function ($q) use ($labels) {
                    foreach ($labels as $l) {
                        $q->orWhereRaw('JSON_SEARCH(institution_roles, \'one\', ?) IS NOT NULL', [$l]);
                    }
                });

                return;
            }

            if ($roleKey === 'academic_coordinator') {
                foreach ($labels as $l) {
                    $outer->orWhereRaw('JSON_SEARCH(role, \'one\', ?) IS NOT NULL', [$l]);
                }

                return;
            }

            foreach ($labels as $l) {
                $outer->orWhereRaw('JSON_SEARCH(institution_roles, \'one\', ?) IS NOT NULL', [$l]);
            }
        });
    }
}

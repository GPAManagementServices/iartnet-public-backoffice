<?php

namespace App\Services;

use App\Http\Resources\InstitutionResource;
use App\Http\Resources\PersonResource;
use App\Models\Institution;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PeoplePageGroupedResponseBuilder
{
    private string $locale;

    private ?int $iartnetInstitutionId = null;

    /** @var array<string, array<string, string>> */
    private array $globalRoles;

    /** @var array<string, array<string, string>> */
    private array $institutionSectionRoles;

    /** @var array<string, array<string, string>> */
    private array $dedicatedSectionRoles;

    private string $noRoleSlug;

    public function __construct()
    {
        $this->globalRoles = config('people_page.global_roles') ?? [];
        $this->institutionSectionRoles = config('people_page.institution_section_roles') ?? [];
        $this->dedicatedSectionRoles = config('people_page.dedicated_section_roles') ?? [];
        $this->noRoleSlug = (string) (config('people_page.no_role_section_slug') ?? 'no_role');
    }

    public function build(Request $request): array
    {
        $this->locale = $request->query('locale', app()->getLocale());
        app()->setLocale($this->locale);

        $query = $this->buildPeopleQuery($request);
        $people = $query->get();

        $allInstitutionIds = $this->collectInstitutionIds($people);
        $institutionsById = $allInstitutionIds->isEmpty()
            ? collect()
            : Institution::whereIn('id', $allInstitutionIds->all())->get()->keyBy('id');

        $iartnetSlug = (string) config('people_page.iartnet_institution_slug', 'iartnet');
        $iartnet = Institution::query()
            ->where("slug->{$this->locale}", $iartnetSlug)
            ->first();
        $this->iartnetInstitutionId = $iartnet ? (int) $iartnet->id : null;

        $academicCoordinatorIds = [];
        $academicCoordinator = [];
        $researchUnitLeads = [];
        $generalCoordinationIds = [];
        $generalCoordination = [];
        /** @var array<int, array{institution: \App\Models\Institution, sections: array<string, array<Person>>}> */
        $byInstitution = [];

        foreach ($people as $person) {
            $globalRoleLabel = $this->getRoleLabel($person->role);
            $globalSlug = $this->matchGlobalRole($globalRoleLabel);
            if ($globalSlug === 'academic_coordinator' && ! in_array($person->id, $academicCoordinatorIds, true)) {
                $academicCoordinatorIds[] = $person->id;
                $academicCoordinator[] = $person;
            }

            $institutionRoles = is_array($person->institution_roles) ? $person->institution_roles : [];
            foreach ($institutionRoles as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $institutionId = isset($row['institution_id']) ? (int) $row['institution_id'] : null;
                if ($institutionId === null) {
                    continue;
                }
                $institution = $institutionsById->get($institutionId);
                if (! $institution instanceof Institution) {
                    continue;
                }
                $roleLabel = $this->getInstitutionRoleLabel($row);
                $sectionSlug = $this->matchSectionRole($roleLabel);

                if ($institutionId === $this->iartnetInstitutionId) {
                    if (! in_array($person->id, $generalCoordinationIds, true)) {
                        $generalCoordinationIds[] = $person->id;
                        $generalCoordination[] = $person;
                    }

                    continue;
                }

                if ($sectionSlug === 'research_unit_lead') {
                    $researchUnitLeads[] = ['institution' => $institution, 'person' => $person];

                    continue;
                }

                if (! isset($byInstitution[$institutionId])) {
                    $byInstitution[$institutionId] = [
                        'institution' => $institution,
                        'sections' => $this->emptySectionsTemplate(),
                    ];
                }
                $slug = $sectionSlug ?? $this->noRoleSlug;
                $byInstitution[$institutionId]['sections'][$slug][] = $person;
            }
        }

        $researchUnitLeadsSorted = collect($researchUnitLeads)
            ->sortBy(fn (array $entry) => $this->institutionSortKey($entry['institution']))
            ->values();

        $institutionIdsOrdered = collect($byInstitution)
            ->sortBy(fn (array $item) => $this->institutionSortKey($item['institution']))
            ->keys();

        $institutionsPayload = [];
        foreach ($institutionIdsOrdered as $instId) {
            $item = $byInstitution[$instId];
            $sectionsOrder = $this->sectionsOrder();
            $sectionsPayload = [];
            foreach ($sectionsOrder as $slug) {
                $persons = $item['sections'][$slug] ?? [];
                $sorted = collect($persons)->sortBy(fn (Person $p) => (string) $p->getTranslation('last_name', $this->locale))->values();
                $sectionsPayload[$slug] = $this->personsToResourceArray($sorted->all(), $request);
            }
            $institutionsPayload[] = [
                'institution' => (new InstitutionResource($item['institution']))->toArray($request),
                'sections' => $sectionsPayload,
            ];
        }

        return [
            'academic_coordinator' => $this->personsToResourceArray($academicCoordinator, $request),
            'research_unit_leads' => $researchUnitLeadsSorted->map(fn (array $entry) => [
                'institution' => (new InstitutionResource($entry['institution']))->toArray($request),
                'person' => (new PersonResource($entry['person']))->toArray($request),
            ])->values()->all(),
            'general_coordination' => $this->personsToResourceArray($generalCoordination, $request),
            'institutions' => $institutionsPayload,
        ];
    }

    private function buildPeopleQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $locale = $request->query('locale', app()->getLocale());
        $query = Person::query()
            ->with(['categories', 'image', 'opengraphPicture', 'peopleRole']);

        $status = $request->query('status', 'published');
        if ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->query('category_id');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId)
                    ->where('type', 'person');
            });
        }

        if ($request->filled('category_slug')) {
            $slug = (string) $request->query('category_slug');
            $query->whereHas('categories', function ($q) use ($locale, $slug) {
                $q->where('type', 'person')
                    ->where("slug->{$locale}", $slug);
            });
        }

        if ($request->filled('institution_id')) {
            $institutionId = (int) $request->query('institution_id');
            $query->whereRaw(
                "JSON_SEARCH(institution_roles, 'one', ?, NULL, '$[*].institution_id') IS NOT NULL",
                [(string) $institutionId]
            );
        }

        if ($request->filled('institution_slug')) {
            $slug = (string) $request->query('institution_slug');
            $institution = Institution::query()
                ->where("slug->{$locale}", $slug)
                ->first();
            if (! $institution) {
                $query->whereRaw('1 = 0');
            } else {
                $institutionId = (int) $institution->id;
                $query->whereRaw(
                    "JSON_SEARCH(institution_roles, 'one', ?, NULL, '$[*].institution_id') IS NOT NULL",
                    [(string) $institutionId]
                );
            }
        }

        if ($request->filled('role')) {
            PeoplePageRoleCatalog::applyRoleFilterToPeopleQuery($query, (string) $request->query('role'));
        }

        return $query;
    }

    /**
     * Ordinamento stabile senza sort_order DB: slug_en → slug_it → slug tradotto, tie-break nome (locale richiesta).
     */
    private function institutionSortKey(Institution $institution): string
    {
        $slugEn = (string) ($institution->slug_en ?? '');
        $slugIt = (string) ($institution->slug_it ?? '');
        $slugFallback = (string) (
            $institution->getTranslation('slug', 'en')
            ?: $institution->getTranslation('slug', 'it')
            ?: $institution->getTranslation('slug', $this->locale)
            ?: ''
        );
        $stem = $slugEn !== '' ? $slugEn : ($slugIt !== '' ? $slugIt : $slugFallback);
        $name = mb_strtolower((string) $institution->getTranslation('name', $this->locale));

        return mb_strtolower($stem).'|'.$name;
    }

    private function collectInstitutionIds(Collection $people): Collection
    {
        $ids = [];
        foreach ($people as $person) {
            $roles = is_array($person->institution_roles) ? $person->institution_roles : [];
            foreach ($roles as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $id = $row['institution_id'] ?? null;
                if ($id !== null && $id !== '') {
                    $ids[(int) $id] = true;
                }
            }
        }

        return collect(array_keys($ids));
    }

    /** @return array<string, array<Person>> */
    private function emptySectionsTemplate(): array
    {
        $template = [];
        foreach (array_keys($this->dedicatedSectionRoles) as $slug) {
            $template[$slug] = [];
        }
        foreach (array_keys($this->institutionSectionRoles) as $slug) {
            $template[$slug] = [];
        }
        $template[$this->noRoleSlug] = [];

        return $template;
    }

    /** @return list<string> */
    private function sectionsOrder(): array
    {
        $order = array_merge(
            array_keys($this->dedicatedSectionRoles),
            array_keys($this->institutionSectionRoles),
            [$this->noRoleSlug]
        );

        return $order;
    }

    private function getRoleLabel($role): ?string
    {
        if (is_array($role)) {
            $v = $role[$this->locale] ?? $role['en'] ?? reset($role);

            return $v === null || $v === '' ? null : trim((string) $v);
        }

        return $role === null || $role === '' ? null : trim((string) $role);
    }

    private function getInstitutionRoleLabel(array $row): ?string
    {
        $role = $row['role'] ?? null;
        if (! is_array($role)) {
            $v = $role;

            return $v === null || $v === '' ? null : trim((string) $v);
        }
        $v = $role[$this->locale] ?? $role['en'] ?? reset($role);
        if ($v === null || $v === '') {
            return null;
        }
        $trimmed = trim((string) $v);

        return strtolower($trimmed) === 'null' ? null : $trimmed;
    }

    private function matchGlobalRole(?string $label): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }
        foreach ($this->globalRoles as $slug => $labels) {
            $expected = trim((string) ($labels[$this->locale] ?? $labels['en'] ?? ''));
            if ($expected !== '' && $label === $expected) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Match institution role label to section slug. Returns 'research_unit_lead' for RUL
     * (handled separately in build), or no_role_slug for null/empty, or section slug.
     */
    private function matchSectionRole(?string $label): ?string
    {
        if ($label === null || $label === '') {
            return $this->noRoleSlug;
        }
        if ($this->matchGlobalRole($label) === 'research_unit_lead') {
            return 'research_unit_lead';
        }
        foreach ($this->dedicatedSectionRoles as $slug => $labels) {
            $expected = trim((string) ($labels[$this->locale] ?? $labels['en'] ?? ''));
            if ($expected !== '' && $label === $expected) {
                return $slug;
            }
        }
        foreach ($this->institutionSectionRoles as $slug => $labels) {
            $expected = trim((string) ($labels[$this->locale] ?? $labels['en'] ?? ''));
            if ($expected !== '' && $label === $expected) {
                return $slug;
            }
        }

        return $this->noRoleSlug;
    }

    /**
     * @param  array<Person>  $persons
     * @return array<int, array<string, mixed>>
     */
    private function personsToResourceArray(array $persons, Request $request): array
    {
        $out = [];
        foreach ($persons as $person) {
            $out[] = (new PersonResource($person))->toArray($request);
        }

        return $out;
    }
}

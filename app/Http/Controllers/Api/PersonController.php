<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonResource;
use App\Models\Institution;
use App\Models\Person;
use App\Services\PeoplePageGroupedResponseBuilder;
use App\Services\PeoplePageRoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'role' => ['sometimes', 'string', Rule::in(PeoplePageRoleCatalog::filterableRoleKeysForQuery())],
        ]);

        if ($request->boolean('grouped')) {
            $builder = new PeoplePageGroupedResponseBuilder;

            return response()->json($builder->build($request));
        }

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

        // Filtra per institution_slug (slug JSON translatable su tabella institutions)
        if ($request->filled('institution_slug')) {
            $slug = (string) $request->query('institution_slug');

            $institution = Institution::query()
                ->where("slug->{$locale}", $slug)
                ->first();

            if (! $institution) {
                // slug inesistente => nessun risultato
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

        $query->orderBy('id');

        if ($this->shouldPaginate($request)) {
            $perPage = $this->perPage($request, 20, 200);

            return PersonResource::collection(
                $query->paginate($perPage)->appends($request->query())
            );
        }

        return PersonResource::collection(
            $query->get()
        );
    }

    public function byInstitution(Request $request, string $institution)
    {
        $locale = $request->query('locale', app()->getLocale());
        $status = $request->query('status', 'published');

        $institutionModel = ctype_digit($institution)
            ? Institution::query()->findOrFail((int) $institution)
            : Institution::query()->where("slug->{$locale}", $institution)->firstOrFail();

        $institutionId = (int) $institutionModel->id;

        $query = Person::query()
            ->with(['categories', 'image', 'opengraphPicture', 'peopleRole'])
            ->where('status', $status)
            ->where(function ($q) use ($institutionId) {
                $q->orWhereJsonContains('institutions', $institutionId);

                $q->orWhereRaw(
                    "JSON_SEARCH(institution_roles, 'one', ?, NULL, '$[*].institution_id') IS NOT NULL",
                    [(string) $institutionId]
                );
            })
            ->orderBy('id');

        return $this->shouldPaginate($request)
        ? PersonResource::collection(
            $query->paginate($this->perPage($request, 20, 200))->appends($request->query())
        )
        : PersonResource::collection(
            $query->get()
        );
    }

    public function show(Request $request, $id)
    {
        $person = Person::with(['categories', 'image', 'opengraphPicture', 'peopleRole'])
            ->findOrFail($id);

        return new PersonResource($person);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $locale = $request->query('locale', app()->getLocale());
        $status = $request->query('status', 'published');

        $person = Person::with(['categories', 'image', 'opengraphPicture', 'peopleRole'])
            ->where('status', $status)
            ->where("slug->{$locale}", $slug)
            ->firstOrFail();

        return new PersonResource($person);
    }

    private function shouldPaginate(Request $request): bool
    {
        // all=true  => NO paginate
        if ($request->boolean('all')) {
            return false;
        }

        // paginate=false => NO paginate
        // (di default paginate è true)
        if ($request->has('paginate') && $request->boolean('paginate') === false) {
            return false;
        }

        return true;
    }

    private function perPage(Request $request, int $default = 20, int $max = 200): int
    {
        $perPage = (int) $request->query('per_page', $default);

        if ($perPage < 1) {
            $perPage = $default;
        }

        if ($perPage > $max) {
            $perPage = $max;
        }

        return $perPage;
    }
}

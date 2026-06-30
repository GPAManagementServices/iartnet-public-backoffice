<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    use Concerns\HandlesApiIndexPagination;

    public function index(Request $request)
    {
        $locale = $request->query('locale', app()->getLocale());

        $query = Institution::query()->with([
            'categories',
            'logoImage',
            'coverImage',
            'opengraphPicture',
        ]);

        $status = $request->query('status', 'published');
        if ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->query('category_id');

            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId)
                    ->where('type', 'institution');
            });
        }

        if ($request->filled('category_slug')) {
            $slug = (string) $request->query('category_slug');

            $query->whereHas('categories', function ($q) use ($locale, $slug) {
                $q->where('type', 'institution')
                    ->where("slug->{$locale}", $slug);
            });
        }

        if ($request->filled('person_id')) {
            $personId = (int) $request->query('person_id');
            $query->whereJsonContains('people', $personId);
        }

        $query->orderByDesc('created_at');

        if ($this->shouldPaginate($request)) {
            return InstitutionResource::collection(
                $query->paginate($this->perPage($request))->appends($request->query())
            );
        }

        return InstitutionResource::collection($query->get());
    }

    public function show(Request $request, $id)
    {
        $institution = Institution::with([
            'categories',
            'logoImage',
            'coverImage',
            'opengraphPicture',
        ])->findOrFail($id);

        return new InstitutionResource($institution);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $locale = $request->query('locale', app()->getLocale());
        $status = $request->query('status', 'published');

        $institution = Institution::with([
            'categories',
            'logoImage',
            'coverImage',
            'opengraphPicture',
        ])
            ->where('status', $status)
            ->where("slug->{$locale}", $slug)
            ->firstOrFail();

        return new InstitutionResource($institution);
    }
}

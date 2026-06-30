<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResearchCatalogueResource;
use App\Models\ResearchCatalogue;
use Illuminate\Http\Request;

class ResearchCatalogueController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = ResearchCatalogue::query()
            ->with(['categories', 'opengraphPicture', 'coverImage'])
            ->orderByDesc('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $locale = $request->query('locale', app()->getLocale());

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->query('category_id');

            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId)
                    ->where('type', 'research_catalogue');
            });
        }

        if ($request->filled('category_slug')) {
            $slug = (string) $request->query('category_slug');

            $query->whereHas('categories', function ($q) use ($locale, $slug) {
                $q->where('type', 'research_catalogue')
                    ->where("slug->{$locale}", $slug);
            });
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return ResearchCatalogueResource::collection($paginator);
    }

    public function show(Request $request, int $id)
    {
        $item = ResearchCatalogue::query()
            ->with(['opengraphPicture', 'coverImage'])
            ->findOrFail($id);

        return new ResearchCatalogueResource($item);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $lang = $request->query('lang');

        $query = ResearchCatalogue::query()->with(['opengraphPicture', 'coverImage']);

        if ($lang === 'it') {
            return new ResearchCatalogueResource($query->where('slug_it', $slug)->firstOrFail());
        }

        if ($lang === 'en') {
            return new ResearchCatalogueResource($query->where('slug_en', $slug)->firstOrFail());
        }

        return new ResearchCatalogueResource(
            $query->where('slug_en', $slug)->orWhere('slug_it', $slug)->firstOrFail()
        );
    }
}

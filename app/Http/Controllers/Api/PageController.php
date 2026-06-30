<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = Page::query()
            ->with(['opengraphPicture', 'coverImage'])
            ->orderByDesc('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return PageResource::collection($paginator);
    }

    public function show(Request $request, int $id)
    {
        $page = Page::query()
            ->with(['opengraphPicture', 'coverImage'])
            ->findOrFail($id);

        return new PageResource($page);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $lang = $request->query('lang');

        $query = Page::query()->with(['opengraphPicture', 'coverImage']);

        if ($lang === 'it') {
            $page = $query->where('slug_it', $slug)->firstOrFail();

            return new PageResource($page);
        }

        if ($lang === 'en') {
            $page = $query->where('slug_en', $slug)->firstOrFail();

            return new PageResource($page);
        }

        $page = $query->where('slug_en', $slug)
            ->orWhere('slug_it', $slug)
            ->firstOrFail();

        return new PageResource($page);
    }
}

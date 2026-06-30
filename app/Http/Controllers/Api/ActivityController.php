<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use Concerns\HandlesApiIndexPagination;

    public function index(Request $request)
    {
        $locale = $request->query('locale', app()->getLocale());

        $query = Activity::query()
            ->with('categories');

        // Status: di default solo published, ma puoi passare ?status=draft|private|published
        $status = $request->query('status', 'published');
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by category id: ?category_id=3
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->query('category_id');

            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId)
                    ->where('type', 'activity');
            });
        }

        // Filter by category slug in the requested locale: ?category_slug=mostre&locale=it
        if ($request->filled('category_slug')) {
            $slug = (string) $request->query('category_slug');

            $query->whereHas('categories', function ($q) use ($locale, $slug) {
                $q->where('type', 'activity')
                    ->where("slug->{$locale}", $slug);
            });
        }

        $query->orderByDesc('start_date');

        if ($this->shouldPaginate($request)) {
            return ActivityResource::collection(
                $query->paginate($this->perPage($request))->appends($request->query())
            );
        }

        return ActivityResource::collection($query->get());
    }

    public function show(Request $request, $id)
    {
        $activity = Activity::with('categories')->findOrFail($id);

        return new ActivityResource($activity);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $locale = $request->query('locale', app()->getLocale());
        $status = $request->query('status', 'published');

        $activity = Activity::with('categories')
            ->where('status', $status)
            ->where("slug->{$locale}", $slug)
            ->firstOrFail();

        return new ActivityResource($activity);
    }
}

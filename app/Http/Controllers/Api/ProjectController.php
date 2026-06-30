<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use Concerns\HandlesApiIndexPagination;

    public function index(Request $request)
    {
        $locale = $request->query('locale', app()->getLocale());

        $query = Project::query()
            ->with(['opengraphPicture', 'coverImage', 'categories']);

        $status = $request->query('status', 'published');
        if ($status) {
            $query->where('status', $status);
        }

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($sub) use ($locale, $q) {
                $sub->where("slug->{$locale}", 'like', '%'.$q.'%');

                if (in_array($locale, ['it', 'en'], true)) {
                    $sub->orWhere('slug_'.$locale, 'like', '%'.$q.'%');
                }
            });
        }

        $query->orderByDesc('updated_at');

        if ($this->shouldPaginate($request)) {
            return ProjectResource::collection(
                $query->paginate($this->perPage($request))->appends($request->query())
            );
        }

        return ProjectResource::collection($query->get());
    }

    public function homepage(Request $request)
    {
        $query = $this->editorialBaseQuery()
            ->published()
            ->visibleInHomepage()
            ->orderedForHomepage();

        return ProjectResource::collection($query->get());
    }

    public function listing(Request $request)
    {
        $query = $this->editorialBaseQuery()
            ->published()
            ->visibleInProjects()
            ->orderedForProjects();

        if ($this->shouldPaginate($request)) {
            return ProjectResource::collection(
                $query->paginate($this->perPage($request))->appends($request->query())
            );
        }

        return ProjectResource::collection($query->get());
    }

    public function show(Request $request, int $id)
    {
        $project = Project::query()
            ->with(['opengraphPicture', 'coverImage', 'categories'])
            ->findOrFail($id);

        return new ProjectResource($project);
    }

    public function showBySlug(Request $request, string $slug)
    {
        $locale = $request->query('locale', app()->getLocale());
        $status = $request->query('status', 'published');

        $query = Project::query()
            ->with(['opengraphPicture', 'coverImage', 'categories'])
            ->where('status', $status)
            ->where(function ($sub) use ($locale, $slug) {
                $sub->where("slug->{$locale}", $slug);

                if (in_array($locale, ['it', 'en'], true)) {
                    $sub->orWhere('slug_'.$locale, $slug);
                }
            });

        $project = $query->firstOrFail();

        return new ProjectResource($project);
    }

    private function editorialBaseQuery(): Builder
    {
        return Project::query()
            ->with(['opengraphPicture', 'coverImage', 'categories']);
    }
}

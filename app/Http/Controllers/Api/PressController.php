<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PressPageResource;
use App\Models\PressPage;
use Illuminate\Http\Request;

class PressController extends Controller
{
    public function show(Request $request)
    {
        $page = PressPage::singletonOrNull();

        if (! $page || $page->status !== 'published') {
            abort(404);
        }

        $page->load([
            'opengraphPicture',
            'contacts' => fn ($query) => $query
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'releases' => fn ($query) => $query
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'releases.coverImage',
            'documents' => fn ($query) => $query
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return new PressPageResource($page);
    }
}

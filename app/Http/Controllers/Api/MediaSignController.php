<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\MediaSignRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use League\Glide\Urls\UrlBuilderFactory;

class MediaSignController extends Controller
{
    /**
     * Firma un URL Glide/Curator per trasformazioni lato lettura (w, h, fit, fm).
     */
    public function __invoke(MediaSignRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $cleanPath = ltrim(rawurldecode($validated['path']), '/');

        $builder = UrlBuilderFactory::create('/curator/', config('app.key'));

        $glideParams = collect($validated)
            ->except('path')
            ->filter()
            ->toArray();

        return response()->json([
            'url' => config('app.url').$builder->getUrl($cleanPath, $glideParams),
        ]);
    }
}

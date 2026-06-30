<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroCarouselItemResource;
use App\Http\Resources\HomepageHighlightItemResource;
use App\Models\HeroCarouselItem;
use App\Models\HomepageHighlightItem;

class HomepageController extends Controller
{
    public function heroCarousel()
    {
        $items = HeroCarouselItem::query()
            ->with('coverMedia')
            ->publishedForHomepage()
            ->orderedForHomepage()
            ->get();

        return HeroCarouselItemResource::collection($items);
    }

    public function highlights()
    {
        $items = HomepageHighlightItem::query()
            ->with('coverMedia')
            ->publishedForHomepage()
            ->orderedForHomepage()
            ->get();

        return HomepageHighlightItemResource::collection($items);
    }
}

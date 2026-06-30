<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\InstitutionController;
use App\Http\Controllers\Api\MediaSignController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\PressController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ResearchCatalogueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Homepage editorial carousels
    Route::get('homepage/hero-carousel', [HomepageController::class, 'heroCarousel']);
    Route::get('homepage/highlights', [HomepageController::class, 'highlights']);

    // Activities
    Route::get('activities', [ActivityController::class, 'index']);
    Route::get('activities/{id}', [ActivityController::class, 'show']);
    Route::get('activities/by-slug/{slug}', [ActivityController::class, 'showBySlug']);
    Route::get('activities/{id}', [ActivityController::class, 'show'])->whereNumber('id');

    // Institutions
    Route::get('institutions', [InstitutionController::class, 'index']);
    Route::get('institutions/{id}', [InstitutionController::class, 'show']);
    Route::get('institutions/by-slug/{slug}', [InstitutionController::class, 'showBySlug']);
    Route::get('institutions/{id}', [InstitutionController::class, 'show'])->whereNumber('id');

    // People
    Route::get('people', [PersonController::class, 'index']);
    Route::get('people/{id}', [PersonController::class, 'show']);
    Route::get('people/by-slug/{slug}', [PersonController::class, 'showBySlug']);
    Route::get('people/{id}', [PersonController::class, 'show'])->whereNumber('id');
    Route::get('institutions/{institution}/people', [PersonController::class, 'byInstitution']);

    // Projects
    Route::get('projects/homepage', [ProjectController::class, 'homepage']);
    Route::get('projects/listing', [ProjectController::class, 'listing']);
    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{id}', [ProjectController::class, 'show']);
    Route::get('projects/by-slug/{slug}', [ProjectController::class, 'showBySlug']);
    Route::get('projects/{id}', [ProjectController::class, 'show'])->whereNumber('id');

    // FAQs
    Route::get('faqs', [FaqController::class, 'index']);
    Route::get('faqs/{id}', [FaqController::class, 'show']);
    Route::get('faqs/by-slug/{slug}', [FaqController::class, 'showBySlug']);
    Route::get('faqs/{id}', [FaqController::class, 'show'])->whereNumber('id');

    // Research catalogue
    Route::get('research-catalogues', [ResearchCatalogueController::class, 'index']);
    Route::get('research-catalogues/{id}', [ResearchCatalogueController::class, 'show']);
    Route::get('research-catalogues/by-slug/{slug}', [ResearchCatalogueController::class, 'showBySlug']);
    Route::get('research-catalogues/{id}', [ResearchCatalogueController::class, 'show'])->whereNumber('id');

    // Pages
    Route::get('pages', [PageController::class, 'index']);
    Route::get('pages/{id}', [PageController::class, 'show']);
    Route::get('pages/by-slug/{slug}', [PageController::class, 'showBySlug']);
    Route::get('pages/{id}', [PageController::class, 'show'])->whereNumber('id');

    // Press (singleton)
    Route::get('press', [PressController::class, 'show']);

    // -----------------------------------------------------------------
    // Media Signer Endpoint per Nuxt (Glide/Curator)
    // -----------------------------------------------------------------
    Route::get('media/sign', MediaSignController::class)
        ->middleware('throttle:media-sign');
});

<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSubtitleApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedProject(array $extra = []): Project
    {
        $project = new Project([
            'title' => ['en' => 'Title EN', 'it' => 'Titolo IT'],
            'slug' => ['en' => 'slug-en-'.uniqid(), 'it' => 'slug-it-'.uniqid()],
            'status' => 'published',
            ...$extra,
        ]);
        $project->save();

        return $project->fresh();
    }

    public function test_api_exposes_subtitle_and_subtitle_translations_for_locale_it(): void
    {
        $project = $this->createPublishedProject([
            'subtitle' => ['en' => 'English subtitle', 'it' => 'Sottotitolo IT'],
        ]);

        $response = $this->getJson('/api/v1/projects/'.$project->id.'?locale=it');

        $response->assertOk();
        $response->assertJsonPath('data.subtitle', 'Sottotitolo IT');
        $response->assertJsonPath('data.subtitle_translations.en', 'English subtitle');
        $response->assertJsonPath('data.subtitle_translations.it', 'Sottotitolo IT');
    }

    public function test_api_subtitle_null_when_not_set(): void
    {
        $project = $this->createPublishedProject();

        $response = $this->getJson('/api/v1/projects/'.$project->id.'?locale=en');

        $response->assertOk();
        $response->assertJsonPath('data.subtitle', null);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityVideoUrlsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedActivity(array $extra = []): Activity
    {
        $activity = new Activity([
            'title' => ['en' => 'Title EN', 'it' => 'Titolo IT'],
            'slug' => ['en' => 'slug-en-'.uniqid(), 'it' => 'slug-it-'.uniqid()],
            'status' => 'published',
            ...$extra,
        ]);
        $activity->save();

        return $activity->fresh();
    }

    public function test_api_exposes_video_urls_array_and_legacy_video_url_first(): void
    {
        $activity = $this->createPublishedActivity([
            'video_urls' => ['https://example.com/a', 'https://example.com/b'],
        ]);

        $response = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=it');

        $response->assertOk();
        $response->assertJsonPath('video_urls', ['https://example.com/a', 'https://example.com/b']);
        $response->assertJsonPath('video_url', 'https://example.com/a');
    }

    public function test_api_empty_videos_returns_empty_array_and_null_legacy(): void
    {
        $activity = $this->createPublishedActivity([
            'video_urls' => null,
            'video_url' => null,
        ]);

        $response = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=it');

        $response->assertOk();
        $response->assertJsonPath('video_urls', []);
        $response->assertJsonPath('video_url', null);
    }

    public function test_api_falls_back_to_legacy_video_url_column_when_video_urls_empty(): void
    {
        $activity = $this->createPublishedActivity([
            'video_urls' => null,
        ]);

        \Illuminate\Support\Facades\DB::table('activities')->where('id', $activity->id)->update([
            'video_url' => 'https://legacy-only.example/watch',
            'video_urls' => null,
        ]);

        $response = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=it');

        $response->assertOk();
        $response->assertJsonPath('video_urls', ['https://legacy-only.example/watch']);
        $response->assertJsonPath('video_url', 'https://legacy-only.example/watch');
    }

    public function test_model_save_normalizes_video_urls_and_syncs_legacy_column(): void
    {
        $activity = $this->createPublishedActivity();

        $activity->video_urls = [
            '  https://a.example/x  ',
            'https://b.example/y',
            'https://a.example/x',
        ];
        $activity->save();
        $activity->refresh();

        $this->assertSame(['https://a.example/x', 'https://b.example/y'], $activity->video_urls);
        $this->assertSame('https://a.example/x', $activity->video_url);
    }
}

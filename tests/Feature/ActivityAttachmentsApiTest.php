<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityAttachmentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_show_includes_attachments_payload(): void
    {
        Config::set('app.url', 'https://cms.example.test');
        Storage::fake('public');
        Storage::disk('public')->put('activities/attachments/doc.pdf', '%PDF-1.4 fake');

        $activity = new Activity([
            'title' => ['en' => 'EVT', 'it' => 'EVT'],
            'slug' => ['en' => 'evt-one', 'it' => 'evt-one'],
            'status' => 'published',
            'attachments' => [
                ['path' => 'activities/attachments/doc.pdf', 'title' => 'Brochure'],
            ],
        ]);
        $activity->save();

        $response = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=en');

        $response->assertOk();
        $response->assertJsonPath('data.attachments.0.id', 1);
        $response->assertJsonPath('data.attachments.0.title', 'Brochure');
        $response->assertJsonPath('data.attachments.0.path', 'activities/attachments/doc.pdf');
        $this->assertArrayNotHasKey('attachment', $response->json('data'));
        $this->assertStringContainsString('activities/attachments/doc.pdf', $response->json('data.attachments.0.url'));
        $this->assertNotNull($response->json('data.attachments.0.mimeType'));
    }

    public function test_activity_show_empty_attachments(): void
    {
        $activity = new Activity([
            'title' => ['en' => 'Empty', 'it' => 'Empty'],
            'slug' => ['en' => 'evt-empty', 'it' => 'evt-empty'],
            'status' => 'published',
            'attachments' => [],
        ]);
        $activity->save();

        $response = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=en');

        $response->assertOk();
        $response->assertJsonPath('data.attachments', []);
        $this->assertArrayNotHasKey('attachment', $response->json('data'));
    }
}

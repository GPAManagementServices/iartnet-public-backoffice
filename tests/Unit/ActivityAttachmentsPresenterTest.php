<?php

namespace Tests\Unit;

use App\Support\ActivityAttachmentsPresenter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityAttachmentsPresenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.url', 'https://cms.example.test');
    }

    public function test_empty_and_null_return_empty_array(): void
    {
        $this->assertSame([], ActivityAttachmentsPresenter::forApi(null));
        $this->assertSame([], ActivityAttachmentsPresenter::forApi([]));
    }

    public function test_serializes_path_objects_and_legacy_strings(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('activities/attachments/one.pdf', '%PDF');
        Storage::disk('public')->put('activities/attachments/two.txt', 'hello');

        $out = ActivityAttachmentsPresenter::forApi([
            ['path' => 'activities/attachments/one.pdf', 'title' => 'One'],
            'activities/attachments/two.txt',
        ]);

        $this->assertCount(2, $out);
        $this->assertSame(1, $out[0]['id']);
        $this->assertSame('One', $out[0]['title']);
        $this->assertSame('activities/attachments/one.pdf', $out[0]['path']);
        $this->assertStringContainsString('activities/attachments/one.pdf', $out[0]['url']);
        $this->assertNotNull($out[0]['mimeType']);

        $this->assertSame(2, $out[1]['id']);
        $this->assertNull($out[1]['title']);
        $this->assertNotNull($out[1]['mimeType']);
    }

    public function test_uses_stored_mime_type_when_present(): void
    {
        Storage::fake('public');

        $out = ActivityAttachmentsPresenter::forApi([
            ['path' => 'activities/attachments/missing-on-disk.bin', 'mime_type' => 'application/pdf'],
        ]);

        $this->assertCount(1, $out);
        $this->assertSame('application/pdf', $out[0]['mimeType']);
    }

    public function test_mime_infers_pdf_from_extension_when_file_missing_on_disk(): void
    {
        Storage::fake('public');

        $out = ActivityAttachmentsPresenter::forApi([
            ['path' => 'activities/attachments/ghost.pdf'],
        ]);

        $this->assertCount(1, $out);
        $this->assertSame('application/pdf', $out[0]['mimeType']);
    }

    public function test_mime_octet_stream_when_unknown_extension_and_missing_file(): void
    {
        Storage::fake('public');

        $out = ActivityAttachmentsPresenter::forApi([
            ['path' => 'activities/attachments/unknown.xyz'],
        ]);

        $this->assertSame('application/octet-stream', $out[0]['mimeType']);
    }
}

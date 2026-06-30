<?php

namespace Tests\Unit;

use App\Support\CuratorMediaApiSerializer;
use Awcodes\Curator\Models\Media;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CuratorMediaApiSerializerTest extends TestCase
{
    private function makeUnsavedMedia(array $overrides = []): Media
    {
        Config::set('app.url', 'https://cms.test');
        Config::set('filesystems.disks.public.url', 'https://cms.test/storage');

        $media = new Media(array_merge([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'x.jpg',
            'path' => 'media/x.jpg',
            'ext' => 'jpg',
            'type' => 'image',
            'caption' => null,
        ], $overrides));
        $media->id = 42;
        $media->exists = true;
        $media->syncOriginal();

        return $media;
    }

    public function test_without_content_captions_uses_media_caption_only(): void
    {
        $media = $this->makeUnsavedMedia(['caption' => 'Library caption']);

        $out = CuratorMediaApiSerializer::serialize($media, 'Alt text', 'it', null);

        $this->assertSame('Alt text', $out['alt']);
        $this->assertSame('Library caption', $out['caption']);
        $this->assertNull($out['captions']);
        $this->assertSame(42, $out['id']);
    }

    public function test_content_captions_override_and_fallback_to_library_caption(): void
    {
        $media = $this->makeUnsavedMedia(['caption' => 'Fallback from media']);

        $out = CuratorMediaApiSerializer::serialize($media, 'Alt', 'it', [
            'it' => 'Didascalia IT',
            'en' => 'Caption EN',
        ]);

        $this->assertSame('Didascalia IT', $out['caption']);
        $this->assertSame('Didascalia IT', $out['captions']['it']);
        $this->assertSame('Caption EN', $out['captions']['en']);

        $outEn = CuratorMediaApiSerializer::serialize($media, 'Alt', 'en', [
            'it' => 'Didascalia IT',
            'en' => 'Caption EN',
        ]);
        $this->assertSame('Caption EN', $outEn['caption']);
    }

    public function test_empty_content_captions_fall_back_to_media_caption(): void
    {
        $media = $this->makeUnsavedMedia(['caption' => 'Only library']);

        $out = CuratorMediaApiSerializer::serialize($media, null, 'it', [
            'it' => '',
            'en' => '',
        ]);

        $this->assertSame('Only library', $out['caption']);
    }

    public function test_null_media_returns_null(): void
    {
        $this->assertNull(CuratorMediaApiSerializer::serialize(null, null, 'it', null));
    }
}

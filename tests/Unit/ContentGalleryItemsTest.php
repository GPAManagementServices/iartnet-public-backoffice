<?php

namespace Tests\Unit;

use App\Support\ContentGalleryItems;
use Tests\TestCase;

class ContentGalleryItemsTest extends TestCase
{
    public function test_legacy_flat_ids_normalize_and_persist(): void
    {
        $items = ContentGalleryItems::normalize([1, 2, 3]);
        $this->assertCount(3, $items);
        $this->assertSame([1, 2, 3], ContentGalleryItems::mediaIds($items));

        $persisted = ContentGalleryItems::toPersisted($items);
        $this->assertSame(1, $persisted[0]['media_id']);
        $this->assertNull($persisted[0]['caption']['it']);
        $this->assertNull($persisted[0]['caption']['en']);
    }

    public function test_nested_legacy_ids(): void
    {
        $items = ContentGalleryItems::normalize([[10, 11]]);
        $this->assertSame([10, 11], ContentGalleryItems::mediaIds($items));
    }

    public function test_structured_media_id_roundtrip(): void
    {
        $raw = [
            ['media_id' => 5, 'caption' => ['it' => ' A ', 'en' => 'B']],
        ];
        $items = ContentGalleryItems::normalize($raw);
        $this->assertSame(5, $items[0]['id']);
        $this->assertSame(' A ', $items[0]['caption']['it']);
        $this->assertSame('B', $items[0]['caption']['en']);
    }

    public function test_caption_for_locale_prefers_content_then_media(): void
    {
        $this->assertSame(
            'IT',
            ContentGalleryItems::captionForLocale(['it' => 'IT', 'en' => 'EN'], 'it', 'media')
        );
        $this->assertSame(
            'media-fallback',
            ContentGalleryItems::captionForLocale(['it' => '', 'en' => ''], 'it', 'media-fallback')
        );
    }

    public function test_duplicate_media_ids_in_legacy_gallery_are_collapsed(): void
    {
        $items = ContentGalleryItems::normalize([1, 1, 2]);
        $this->assertCount(2, $items);
        $this->assertSame([1, 2], ContentGalleryItems::mediaIds($items));
    }

    public function test_to_persisted_matches_filament_shape(): void
    {
        $items = ContentGalleryItems::normalize([
            ['media_id' => 9, 'caption' => ['it' => 'a', 'en' => 'b']],
        ]);
        $p = ContentGalleryItems::toPersisted($items);
        $this->assertSame(9, $p[0]['media_id']);
        $this->assertSame('a', $p[0]['caption']['it']);
        $this->assertSame('b', $p[0]['caption']['en']);
    }
}

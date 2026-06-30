<?php

namespace Tests\Feature;

use App\Models\Activity;
use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Richiede DB di test (SQLite in-memory consigliato). Esempio Docker:
 * docker compose exec -T app sh -c "DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/ActivityMediaCaptionsApiTest.php"
 *
 * Le migrazioni creano le FK verso la tabella `media` (Curator). Su database già popolati che
 * hanno ancora vincoli verso `curator_media` (tabella inesistente in questo progetto), eseguire
 * `2026_03_28_180000_retarget_curator_media_foreign_keys_to_media_table` per riallinearle a `media`.
 */
class ActivityMediaCaptionsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createMedia(string $suffix, ?string $libraryCaption = null): Media
    {
        return Media::create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => "img-{$suffix}.jpg",
            'path' => "media/img-{$suffix}.jpg",
            'ext' => 'jpg',
            'type' => 'image',
            'caption' => $libraryCaption,
        ]);
    }

    public function test_activity_api_exposes_cover_and_gallery_captions_per_locale(): void
    {
        $cover = $this->createMedia('cover', 'LibCover');
        $g1 = $this->createMedia('g1', 'LibG1');

        $activity = new Activity([
            'title' => ['en' => 'Evt', 'it' => 'Evt'],
            'slug' => ['en' => 'evt-cap', 'it' => 'evt-cap'],
            'status' => 'published',
            'cover_image_id' => $cover->id,
            'gallery' => [
                [
                    'media_id' => $g1->id,
                    'caption' => ['it' => 'G IT', 'en' => 'G EN'],
                ],
            ],
        ]);
        $activity->setTranslation('cover_image_alt', 'it', 'Alt IT');
        $activity->setTranslation('cover_image_alt', 'en', 'Alt EN');
        $activity->setTranslation('cover_image_caption', 'it', 'Did cover IT');
        $activity->setTranslation('cover_image_caption', 'en', 'Did cover EN');
        $activity->save();

        $it = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=it');
        $it->assertOk();
        $it->assertJsonPath('data.media.cover_image.alt', 'Alt IT');
        $it->assertJsonPath('data.media.cover_image.captions.it', 'Did cover IT');
        $it->assertJsonPath('data.media.cover_image.captions.en', 'Did cover EN');
        $it->assertJsonPath('data.media.cover_image.caption', 'Did cover IT');
        $it->assertJsonPath('data.media.gallery.0.caption', 'G IT');
        $it->assertJsonPath('data.media.gallery.0.captions.it', 'G IT');
        $it->assertJsonPath('data.translations.cover_image_caption.it', 'Did cover IT');

        $en = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=en');
        $en->assertOk();
        $en->assertJsonPath('data.media.cover_image.caption', 'Did cover EN');
        $en->assertJsonPath('data.media.gallery.0.caption', 'G EN');
    }

    public function test_activity_legacy_gallery_ids_still_resolve_after_normalize(): void
    {
        $g1 = $this->createMedia('legacy1');
        $g2 = $this->createMedia('legacy2');

        $activity = new Activity([
            'title' => ['en' => 'Leg', 'it' => 'Leg'],
            'slug' => ['en' => 'evt-legacy', 'it' => 'evt-legacy'],
            'status' => 'published',
            'gallery' => [$g1->id, $g2->id],
        ]);
        $activity->save();

        $res = $this->getJson('/api/v1/activities/'.$activity->id.'?locale=it');
        $res->assertOk();
        $this->assertCount(2, $res->json('data.media.gallery'));
        $this->assertSame($g1->id, $res->json('data.media.gallery.0.id'));
        $this->assertSame($g2->id, $res->json('data.media.gallery.1.id'));
        $this->assertNull($res->json('data.media.gallery.0.captions.it'));
    }
}

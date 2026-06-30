<?php

namespace Tests\Feature;

use App\Models\HeroCarouselItem;
use App\Models\HomepageHighlightItem;
use App\Support\HomepageCanonicalImporter;
use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HomepageCarouselApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_endpoint_returns_published_items_ordered_without_cover_field(): void
    {
        Config::set('app.url', 'https://cms.example.test');
        Config::set('filesystems.disks.public.url', 'https://cms.example.test/storage');

        $response = $this->getJson('/api/v1/homepage/hero-carousel');

        $response->assertOk();
        $this->assertSame('Bagatella_2a', $response->json('data.0.title'));
        $this->assertSame('/digital-object/LO12124798', $response->json('data.0.slug'));
        $this->assertSame(649, $response->json('data.0.media.id'));
        $this->assertArrayNotHasKey('cover', $response->json('data.0'));
        $this->assertSame(
            [
                'LO12124798',
                'OA_4t010-00050',
                'MIDFICCD_MIDF_7835154352771AI652S303AI652BP02_SC17_0912026_03_03',
            ],
            collect($response->json('data'))->pluck('digital_object_slug')->take(3)->all(),
        );
    }

    public function test_hero_endpoint_excludes_unpublished_and_items_without_media(): void
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'hidden.jpg',
            'path' => 'media/hidden.jpg',
            'ext' => 'jpg',
            'type' => 'image',
        ]);

        HeroCarouselItem::query()->create([
            'title' => 'Hidden',
            'digital_object_slug' => 'HIDDEN',
            'cover_media_id' => $media->id,
            'sort_order' => 99,
            'is_published' => false,
        ]);

        HeroCarouselItem::query()->create([
            'title' => 'No media',
            'digital_object_slug' => 'NO_MEDIA',
            'sort_order' => 100,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/homepage/hero-carousel');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('digital_object_slug');
        $this->assertFalse($slugs->contains('HIDDEN'));
        $this->assertFalse($slugs->contains('NO_MEDIA'));
    }

    public function test_highlights_endpoint_returns_published_items_ordered_with_curator_and_iiif(): void
    {
        $response = $this->getJson('/api/v1/homepage/highlights');

        $response->assertOk();
        $this->assertCount(6, $response->json('data'));
        $this->assertSame('Giuseppe Bossi', $response->json('data.0.title.autore'));
        $this->assertSame('media/320cb76b-cfa6-467a-851d-41c77e32264a.jpg', $response->json('data.0.media.path'));
        $this->assertSame('97a0d1cf-f1fd-4f04-b10f-6269041aa23g.tif', $response->json('data.4.cover_iiif_identifier'));
        $this->assertNull($response->json('data.4.media'));
    }

    public function test_highlights_endpoint_keeps_carlo_canonical_text_and_author(): void
    {
        $response = $this->getJson('/api/v1/homepage/highlights');

        $response->assertOk();
        $carlo = collect($response->json('data'))
            ->firstWhere('digital_object_slug', 'MIDFICCD_MIDF_7088549891671AI675S309');

        $this->assertSame('Anonymous', $carlo['title']['autore']);
        $this->assertStringContainsString('Carlo De Veroli (1890–1938)', $carlo['description']);
        $this->assertStringContainsString('the sitter’s character', $carlo['description']);
        $this->assertStringContainsString('the personal aspirations of a young sculptor.', $carlo['description']);
    }

    public function test_highlights_endpoint_returns_empty_data_when_no_items_publishable(): void
    {
        HomepageHighlightItem::query()->update(['is_published' => false]);

        $response = $this->getJson('/api/v1/homepage/highlights');

        $response->assertOk();
        $response->assertJsonPath('data', []);
    }

    public function test_hero_endpoint_returns_empty_data_when_no_items_publishable(): void
    {
        HeroCarouselItem::query()->update(['is_published' => false]);

        $response = $this->getJson('/api/v1/homepage/hero-carousel');

        $response->assertOk();
        $response->assertJsonPath('data', []);
    }

    public function test_canonical_import_is_idempotent(): void
    {
        $beforeHero = HeroCarouselItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['title', 'digital_object_slug', 'cover_media_id', 'sort_order', 'is_published'])
            ->toArray();
        $beforeHighlights = HomepageHighlightItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['title_variant', 'title', 'author', 'subtitle_1', 'subtitle_2', 'description', 'digital_object_slug', 'cover_media_id', 'cover_iiif_identifier', 'sort_order', 'is_published'])
            ->toArray();

        (new HomepageCanonicalImporter())->import();
        (new HomepageCanonicalImporter())->import();

        $this->assertSame(12, HeroCarouselItem::query()->count());
        $this->assertSame(7, HomepageHighlightItem::query()->count());
        $this->assertSame(6, HomepageHighlightItem::query()->publishedForHomepage()->count());
        $this->assertSame(12, HeroCarouselItem::query()->distinct('digital_object_slug')->count('digital_object_slug'));
        $this->assertSame(7, HomepageHighlightItem::query()->distinct('digital_object_slug')->count('digital_object_slug'));
        $this->assertSame(
            $beforeHero,
            HeroCarouselItem::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['title', 'digital_object_slug', 'cover_media_id', 'sort_order', 'is_published'])
                ->toArray(),
        );
        $this->assertSame(
            $beforeHighlights,
            HomepageHighlightItem::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['title_variant', 'title', 'author', 'subtitle_1', 'subtitle_2', 'description', 'digital_object_slug', 'cover_media_id', 'cover_iiif_identifier', 'sort_order', 'is_published'])
                ->toArray(),
        );
    }

    public function test_canonical_import_accepts_expected_media_id_with_matching_uuid_and_different_extension(): void
    {
        $this->upsertMedia(649, 'media/959c418f-3ea3-4469-bff1-91f07a29408a.webp');

        (new HomepageCanonicalImporter(insertMissingMedia: false))->import([$this->bagatellaHeroItem()], []);

        $this->assertSame(649, HeroCarouselItem::query()->where('digital_object_slug', 'LO12124798')->value('cover_media_id'));
    }

    public function test_canonical_import_fails_when_expected_media_id_has_different_uuid(): void
    {
        $this->upsertMedia(649, 'media/00000000-0000-0000-0000-000000000000.webp');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('media mismatch');

        (new HomepageCanonicalImporter(insertMissingMedia: false))->import([$this->bagatellaHeroItem()], []);
    }

    public function test_canonical_import_fails_when_expected_uuid_belongs_to_different_media_id(): void
    {
        HeroCarouselItem::query()->where('digital_object_slug', 'LO12124798')->update(['cover_media_id' => null]);
        Media::query()->whereKey(649)->delete();
        $this->upsertMedia(999, 'media/959c418f-3ea3-4469-bff1-91f07a29408a.webp');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('media collision');

        (new HomepageCanonicalImporter(insertMissingMedia: false))->import([$this->bagatellaHeroItem()], []);
    }

    public function test_canonical_import_fails_when_required_media_is_missing(): void
    {
        HeroCarouselItem::query()->where('digital_object_slug', 'LO12124798')->update(['cover_media_id' => null]);
        Media::query()->whereKey(649)->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('required media missing');

        (new HomepageCanonicalImporter(insertMissingMedia: false))->import([$this->bagatellaHeroItem()], []);
    }

    public function test_highlights_endpoint_keeps_full_carlo_canonical_record_hash(): void
    {
        $response = $this->getJson('/api/v1/homepage/highlights');

        $response->assertOk();
        $carlo = collect($response->json('data'))
            ->firstWhere('digital_object_slug', 'MIDFICCD_MIDF_7088549891671AI675S309');

        $this->assertSame('Anonymous', $carlo['title']['autore']);
        $this->assertSame("Carlo De Veroli with the plaster cast of Pope Rezzonico's bust after Antonio Canova", $carlo['title']['titolo']);
        $this->assertSame('/digital-object/MIDFICCD_MIDF_7088549891671AI675S309', $carlo['link']);
        $this->assertSame('e43e09d3-e28b-4502-a5ad-671b856985c9.jpg', $carlo['cover_iiif_identifier']);
        $this->assertSame(
            '7AC6183C83887403994D344D24F903BC16FC5BF0579BC72A70F7A2081A220314',
            strtoupper(hash('sha256', $carlo['description'])),
        );
        $this->assertStringNotContainsString('Anomymous', $carlo['title']['autore']);
        $this->assertStringNotContainsString('â€“', $carlo['description']);
        $this->assertStringNotContainsString('â€™', $carlo['description']);
    }

    /**
     * @return array<string, mixed>
     */
    private function bagatellaHeroItem(): array
    {
        return [
            'title' => 'Bagatella_2a',
            'digital_object_slug' => 'LO12124798',
            'sort_order' => 0,
            'is_published' => true,
            'cover_media_path' => 'media/959c418f-3ea3-4469-bff1-91f07a29408a.jpg',
            'expected_media_id' => 649,
        ];
    }

    private function upsertMedia(int $id, string $path): void
    {
        $now = now();
        $name = basename($path);
        $payload = [
            'id' => $id,
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => $name,
            'path' => $path,
            'width' => null,
            'height' => null,
            'size' => null,
            'type' => 'image',
            'ext' => pathinfo($name, PATHINFO_EXTENSION),
            'alt' => null,
            'title' => null,
            'description' => null,
            'caption' => null,
            'exif' => null,
            'curations' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table(app(config('curator.model'))->getTable())->updateOrInsert(['id' => $id], $payload);
    }
}

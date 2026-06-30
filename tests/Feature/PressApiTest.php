<?php

namespace Tests\Feature;

use App\Models\PressContact;
use App\Models\PressDocument;
use App\Models\PressPage;
use App\Models\PressRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PressApiTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPage(array $extra = []): PressPage
    {
        $page = PressPage::resolveSingleton();
        $page->fill([
            'status' => 'published',
            'title' => 'Press area',
            'intro' => 'Introductory copy.',
            'meta_title' => 'Press area',
            'meta_description' => 'Press meta description.',
            ...$extra,
        ]);
        $page->save();

        return $page->fresh();
    }

    public function test_press_endpoint_returns_404_when_singleton_missing(): void
    {
        $this->getJson('/api/v1/press')->assertNotFound();
    }

    public function test_press_endpoint_returns_404_when_page_is_draft(): void
    {
        $page = PressPage::resolveSingleton();
        $page->status = 'draft';
        $page->save();

        $this->getJson('/api/v1/press')->assertNotFound();
    }

    public function test_press_endpoint_returns_populated_contract(): void
    {
        Config::set('app.url', 'https://cms.example.test');
        Storage::fake('public');
        Storage::disk('public')->put('press/files/brochure.pdf', '%PDF-1.4 fake');

        $page = $this->publishedPage();

        PressContact::create([
            'press_page_id' => $page->id,
            'label' => 'Contact',
            'email' => 'info@iartnet.it',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        PressRelease::create([
            'press_page_id' => $page->id,
            'title' => 'Release title',
            'description' => 'Release description',
            'destination_type' => 'external',
            'external_url' => 'https://example.test/release',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        PressDocument::create([
            'press_page_id' => $page->id,
            'category' => 'Press release',
            'title' => 'Document title',
            'date' => '2026-05-22',
            'destination_type' => 'file',
            'file_path' => 'press/files/brochure.pdf',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/press');

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Press area');
        $response->assertJsonPath('data.intro', 'Introductory copy.');
        $response->assertJsonPath('data.seo.title', 'Press area');
        $response->assertJsonPath('data.seo.description', 'Press meta description.');
        $response->assertJsonPath('data.contacts.0.email', 'info@iartnet.it');
        $response->assertJsonPath('data.pressReleases.0.destination.type', 'external');
        $response->assertJsonPath('data.documents.0.date', '2026-05-22');
        $response->assertJsonPath('data.documents.0.destination.type', 'file');
        $response->assertJsonPath('data.documents.0.destination.fileName', 'brochure.pdf');
        $this->assertIsArray($response->json('data.contacts'));
        $this->assertArrayNotHasKey('status', $response->json('data'));
        $this->assertArrayNotHasKey('file_path', $response->json('data.documents.0'));
    }

    public function test_press_endpoint_excludes_draft_children(): void
    {
        $page = $this->publishedPage();

        PressContact::create([
            'press_page_id' => $page->id,
            'label' => 'Hidden',
            'email' => 'hidden@example.test',
            'sort_order' => 1,
            'status' => 'draft',
        ]);

        PressRelease::create([
            'press_page_id' => $page->id,
            'title' => 'Hidden release',
            'destination_type' => 'none',
            'sort_order' => 1,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/press');

        $response->assertOk();
        $response->assertJsonPath('data.contacts', []);
        $response->assertJsonPath('data.pressReleases', []);
        $response->assertJsonPath('data.documents', []);
    }

    public function test_press_endpoint_returns_empty_collections_when_published_page_has_no_children(): void
    {
        $this->publishedPage();

        $response = $this->getJson('/api/v1/press');

        $response->assertOk();
        $response->assertJsonPath('data.contacts', []);
        $response->assertJsonPath('data.pressReleases', []);
        $response->assertJsonPath('data.documents', []);
    }

    public function test_press_items_without_destination_return_null_destination(): void
    {
        $page = $this->publishedPage();

        PressRelease::create([
            'press_page_id' => $page->id,
            'title' => 'Static release',
            'description' => 'No link',
            'destination_type' => 'none',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/press');

        $response->assertOk();
        $response->assertJsonPath('data.pressReleases.0.destination', null);
    }

    public function test_press_children_are_ordered_by_sort_order_then_id(): void
    {
        $page = $this->publishedPage();

        $second = PressDocument::create([
            'press_page_id' => $page->id,
            'title' => 'Second',
            'sort_order' => 20,
            'status' => 'published',
            'destination_type' => 'none',
        ]);
        $first = PressDocument::create([
            'press_page_id' => $page->id,
            'title' => 'First',
            'sort_order' => 10,
            'status' => 'published',
            'destination_type' => 'none',
        ]);

        $response = $this->getJson('/api/v1/press');

        $response->assertOk();
        $this->assertSame(
            [(string) $first->id, (string) $second->id],
            collect($response->json('data.documents'))->pluck('id')->all(),
        );
    }

    public function test_singleton_key_is_unique(): void
    {
        PressPage::resolveSingleton();

        $this->expectException(\Illuminate\Database\QueryException::class);

        PressPage::query()->create([
            'singleton_key' => PressPage::SINGLETON_KEY,
            'status' => 'draft',
            'title' => 'Duplicate',
        ]);
    }
}

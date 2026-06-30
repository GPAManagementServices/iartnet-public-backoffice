<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Support\ProjectEditorialPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProjectEditorialVisibilityApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(array $extra = []): Project
    {
        $project = new Project([
            'title' => ['en' => 'Title EN '.uniqid(), 'it' => 'Titolo IT '.uniqid()],
            'slug' => ['en' => 'slug-en-'.uniqid(), 'it' => 'slug-it-'.uniqid()],
            'status' => 'published',
            ...$extra,
        ]);
        $project->save();

        return $project->fresh();
    }

    public function test_normalize_order_maps_zero_and_empty_to_null(): void
    {
        $this->assertNull(ProjectEditorialPosition::normalizeOrder(null));
        $this->assertNull(ProjectEditorialPosition::normalizeOrder(''));
        $this->assertNull(ProjectEditorialPosition::normalizeOrder('0'));
        $this->assertNull(ProjectEditorialPosition::normalizeOrder(0));
        $this->assertSame(3, ProjectEditorialPosition::normalizeOrder(3));
        $this->assertSame(7, ProjectEditorialPosition::normalizeOrder('7'));
    }

    public function test_homepage_order_zero_is_persisted_as_null(): void
    {
        $project = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => 0,
        ]);

        $this->assertNull($project->homepage_order);
    }

    public function test_projects_order_zero_is_persisted_as_null(): void
    {
        $project = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => 0,
        ]);

        $this->assertNull($project->projects_order);
    }

    public function test_homepage_order_empty_string_is_persisted_as_null(): void
    {
        $project = new Project([
            'title' => ['en' => 'T', 'it' => 'T'],
            'slug' => ['en' => 'slug-empty-hp-'.uniqid(), 'it' => 'slug-empty-hp-it-'.uniqid()],
            'status' => 'published',
            'show_in_homepage' => true,
            'homepage_order' => '',
        ]);
        $project->save();

        $this->assertNull($project->fresh()->homepage_order);
    }

    public function test_projects_order_empty_string_is_persisted_as_null(): void
    {
        $project = new Project([
            'title' => ['en' => 'T', 'it' => 'T'],
            'slug' => ['en' => 'slug-empty-pr-'.uniqid(), 'it' => 'slug-empty-pr-it-'.uniqid()],
            'status' => 'published',
            'show_in_projects' => true,
            'projects_order' => '',
        ]);
        $project->save();

        $this->assertNull($project->fresh()->projects_order);
    }

    public function test_exclusive_homepage_position_dissociates_previous_holder(): void
    {
        $a = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => 10,
            'projects_order' => 5,
        ]);

        $b = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => null,
        ]);

        $b->homepage_order = 10;
        $b->save();

        $a->refresh();
        $b->refresh();

        $this->assertSame(10, $b->homepage_order);
        $this->assertNull($a->homepage_order);
        $this->assertTrue($a->show_in_homepage);
        $this->assertSame(5, $a->projects_order);
        $this->assertNull($b->projects_order);
    }

    public function test_exclusive_projects_position_dissociates_previous_holder(): void
    {
        $a = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => 5,
            'homepage_order' => 10,
        ]);

        $b = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => null,
        ]);

        $b->projects_order = 5;
        $b->save();

        $a->refresh();
        $b->refresh();

        $this->assertSame(5, $b->projects_order);
        $this->assertNull($a->projects_order);
        $this->assertTrue($a->show_in_projects);
        $this->assertSame(10, $a->homepage_order);
        $this->assertNull($b->homepage_order);
    }

    public function test_homepage_position_change_does_not_alter_projects_order_context(): void
    {
        $a = $this->createProject([
            'show_in_homepage' => true,
            'show_in_projects' => true,
            'homepage_order' => 10,
            'projects_order' => 5,
        ]);

        $b = $this->createProject(['show_in_homepage' => true]);
        $b->homepage_order = 10;
        $b->save();

        $a->refresh();

        $this->assertNull($a->homepage_order);
        $this->assertSame(5, $a->projects_order);
    }

    public function test_homepage_includes_only_published_with_show_in_homepage(): void
    {
        $visible = $this->createProject(['show_in_homepage' => true, 'homepage_order' => 1]);
        $this->createProject(['status' => 'draft', 'show_in_homepage' => true]);
        $this->createProject(['show_in_homepage' => false]);

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$visible->id], $ids);
        $response->assertJsonMissingPath('data.0.show_in_homepage');
    }

    public function test_homepage_returns_empty_when_none_enabled(): void
    {
        $this->createProject(['show_in_homepage' => false]);

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_homepage_orders_by_homepage_order_with_nulls_last(): void
    {
        $low = $this->createProject(['show_in_homepage' => true, 'homepage_order' => 1]);
        $high = $this->createProject(['show_in_homepage' => true, 'homepage_order' => 10]);
        $nullOrder = $this->createProject(['show_in_homepage' => true, 'homepage_order' => null]);

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $this->assertSame(
            [$low->id, $high->id, $nullOrder->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_homepage_legacy_order_for_items_without_position(): void
    {
        $older = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => null,
            'updated_at' => Carbon::parse('2020-01-01'),
        ]);
        $newer = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => null,
            'updated_at' => Carbon::parse('2024-01-01'),
        ]);

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $this->assertSame(
            [$newer->id, $older->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_homepage_mixed_positioned_and_legacy_order(): void
    {
        $a = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => 20,
        ]);
        $c = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => 10,
        ]);
        $d = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => null,
            'updated_at' => Carbon::parse('2024-06-01'),
        ]);
        $b = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => null,
            'updated_at' => Carbon::parse('2020-01-01'),
        ]);

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $this->assertSame(
            [$c->id, $a->id, $d->id, $b->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_homepage_returns_all_enabled_without_numeric_cap(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createProject([
                'show_in_homepage' => true,
                'homepage_order' => $i,
            ]);
        }

        $response = $this->getJson('/api/v1/projects/homepage?locale=en');

        $response->assertOk();
        $this->assertCount(12, $response->json('data'));
    }

    public function test_listing_includes_only_published_with_show_in_projects(): void
    {
        $visible = $this->createProject(['show_in_projects' => true, 'projects_order' => 1]);
        $this->createProject(['status' => 'draft', 'show_in_projects' => true]);
        $this->createProject(['show_in_projects' => false]);

        $response = $this->getJson('/api/v1/projects/listing?locale=en&all=true');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$visible->id], $ids);
    }

    public function test_listing_orders_by_projects_order_with_nulls_last(): void
    {
        $low = $this->createProject(['show_in_projects' => true, 'projects_order' => 2]);
        $high = $this->createProject(['show_in_projects' => true, 'projects_order' => 20]);
        $nullOrder = $this->createProject(['show_in_projects' => true, 'projects_order' => null]);

        $response = $this->getJson('/api/v1/projects/listing?locale=en&all=true');

        $response->assertOk();
        $this->assertSame(
            [$low->id, $high->id, $nullOrder->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_listing_legacy_order_for_items_without_position(): void
    {
        $older = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => null,
            'updated_at' => Carbon::parse('2020-01-01'),
        ]);
        $newer = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => null,
            'updated_at' => Carbon::parse('2024-01-01'),
        ]);

        $response = $this->getJson('/api/v1/projects/listing?locale=en&all=true');

        $response->assertOk();
        $this->assertSame(
            [$newer->id, $older->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_listing_mixed_positioned_and_legacy_order(): void
    {
        $a = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => 20,
        ]);
        $c = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => 10,
        ]);
        $d = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => null,
            'updated_at' => Carbon::parse('2024-06-01'),
        ]);
        $b = $this->createProject([
            'show_in_projects' => true,
            'projects_order' => null,
            'updated_at' => Carbon::parse('2020-01-01'),
        ]);

        $response = $this->getJson('/api/v1/projects/listing?locale=en&all=true');

        $response->assertOk();
        $this->assertSame(
            [$c->id, $a->id, $d->id, $b->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_listing_all_true_returns_more_than_default_page_size(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->createProject(['show_in_projects' => true, 'projects_order' => $i]);
        }

        $paginated = $this->getJson('/api/v1/projects/listing?locale=en&status=published');
        $paginated->assertOk();
        $this->assertCount(20, $paginated->json('data'));

        $all = $this->getJson('/api/v1/projects/listing?locale=en&all=true');
        $all->assertOk();
        $this->assertCount(21, $all->json('data'));
    }

    public function test_legacy_index_includes_all_published_regardless_of_editorial_flags(): void
    {
        $hidden = $this->createProject([
            'show_in_homepage' => false,
            'show_in_projects' => false,
        ]);

        $response = $this->getJson('/api/v1/projects?locale=en&status=published&all=true');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($hidden->id, $ids);
    }

    public function test_legacy_index_still_orders_by_updated_at_desc(): void
    {
        $older = $this->createProject(['updated_at' => Carbon::parse('2020-06-01')]);
        $newer = $this->createProject(['updated_at' => Carbon::parse('2025-06-01')]);

        $response = $this->getJson('/api/v1/projects?locale=en&status=published&all=true');

        $response->assertOk();
        $this->assertSame(
            [$newer->id, $older->id],
            collect($response->json('data'))->pluck('id')->take(2)->all(),
        );
    }

    public function test_dissociation_does_not_bump_updated_at_of_previous_holder(): void
    {
        $legacyUpdatedAt = Carbon::parse('2019-03-15 10:00:00');

        $a = $this->createProject([
            'show_in_homepage' => true,
            'homepage_order' => 10,
            'updated_at' => $legacyUpdatedAt,
        ]);

        $b = $this->createProject(['show_in_homepage' => true]);
        $b->homepage_order = 10;
        $b->save();

        $a->refresh();

        $this->assertNull($a->homepage_order);
        $this->assertTrue($a->updated_at->equalTo($legacyUpdatedAt));
    }
}

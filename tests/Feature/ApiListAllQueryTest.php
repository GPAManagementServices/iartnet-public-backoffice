<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Institution;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mail_IartnetRicerca_20260515 — listing API: all=true returns full result set (no 20-item cap).
 */
class ApiListAllQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutions_index_all_true_returns_more_than_default_page_size(): void
    {
        for ($i = 0; $i < 21; $i++) {
            Institution::create([
                'name' => ['en' => "Inst {$i}", 'it' => "Ist {$i}"],
                'slug' => ['en' => "inst-all-{$i}", 'it' => "ist-all-{$i}"],
                'status' => 'published',
            ]);
        }

        $paginated = $this->getJson('/api/v1/institutions?locale=en&status=published');
        $paginated->assertOk();
        $this->assertCount(20, $paginated->json('data'));
        $this->assertGreaterThan(1, $paginated->json('meta.last_page'));

        $all = $this->getJson('/api/v1/institutions?locale=en&status=published&all=true');
        $all->assertOk();
        $this->assertCount(21, $all->json('data'));
        $this->assertNull($all->json('meta'));
    }

    public function test_activities_index_all_true_returns_more_than_default_page_size(): void
    {
        for ($i = 0; $i < 21; $i++) {
            Activity::create([
                'title' => ['en' => "Act {$i}", 'it' => "Att {$i}"],
                'slug' => ['en' => "act-all-{$i}", 'it' => "att-all-{$i}"],
                'status' => 'published',
            ]);
        }

        $paginated = $this->getJson('/api/v1/activities?locale=en&status=published');
        $paginated->assertOk();
        $this->assertCount(20, $paginated->json('data'));

        $all = $this->getJson('/api/v1/activities?locale=en&status=published&all=true');
        $all->assertOk();
        $this->assertCount(21, $all->json('data'));
    }

    public function test_projects_index_all_true_returns_more_than_default_page_size(): void
    {
        for ($i = 0; $i < 21; $i++) {
            Project::create([
                'title' => ['en' => "Proj {$i}", 'it' => "Prog {$i}"],
                'slug' => ['en' => "proj-all-{$i}", 'it' => "prog-all-{$i}"],
                'status' => 'published',
            ]);
        }

        $paginated = $this->getJson('/api/v1/projects?locale=en&status=published');
        $paginated->assertOk();
        $this->assertCount(20, $paginated->json('data'));

        $all = $this->getJson('/api/v1/projects?locale=en&status=published&all=true');
        $all->assertOk();
        $this->assertCount(21, $all->json('data'));
    }
}

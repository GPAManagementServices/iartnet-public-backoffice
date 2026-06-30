<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La root reindirizza al pannello admin.
     */
    public function test_root_redirects_to_admin(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
        $response->assertStatus(302);
    }

    /**
     * L'API v1 projects risponde con JSON (lista paginata).
     */
    public function test_api_v1_projects_returns_json(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MediaSignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_media_sign_returns_url_for_valid_path(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/2024/01/sample.jpg',
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['url']);
        $this->assertStringContainsString('/curator/', $response->json('url'));
    }

    public function test_media_sign_accepts_leading_slash_on_path(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => '/media/2024/01/sample.jpg',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('/curator/', $response->json('url'));
    }

    public function test_media_sign_includes_glide_params_webp(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/x/photo.png',
            'w' => 640,
            'h' => 480,
            'fit' => 'crop',
            'fm' => 'webp',
        ]));

        $response->assertOk();
        $url = $response->json('url');
        $this->assertStringContainsString('w=640', $url);
        $this->assertStringContainsString('h=480', $url);
        $this->assertStringContainsString('fit=crop', $url);
        $this->assertStringContainsString('fm=webp', $url);
    }

    public function test_media_sign_accepts_fm_png_and_jpg(): void
    {
        foreach (['png', 'jpg'] as $fm) {
            $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
                'path' => 'media/a/b.jpg',
                'fm' => $fm,
            ]));
            $response->assertOk();
            $this->assertStringContainsString('fm='.$fm, $response->json('url'));
        }
    }

    public function test_media_sign_rejects_invalid_fm(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/a/b.jpg',
            'fm' => 'avif',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['fm']);
    }

    public function test_media_sign_rejects_w_over_max(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/a/b.jpg',
            'w' => 3000,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['w']);
    }

    public function test_media_sign_rejects_h_below_min(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/a/b.jpg',
            'h' => 5,
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['h']);
    }

    public function test_media_sign_rejects_path_without_media_prefix(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'public/evil.jpg',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_media_sign_rejects_path_traversal(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/../../../etc/passwd',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_media_sign_rejects_path_with_backslash(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media\\windows\\path.jpg',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_media_sign_rejects_invalid_characters_in_path(): void
    {
        $response = $this->getJson('/api/v1/media/sign?'.http_build_query([
            'path' => 'media/hello world/x.jpg',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['path']);
    }

    public function test_media_sign_rate_limits_by_ip(): void
    {
        Config::set('media.sign_max_attempts_per_minute', 2);

        $query = http_build_query(['path' => 'media/rate/lim.jpg']);

        $this->getJson('/api/v1/media/sign?'.$query)->assertOk();
        $this->getJson('/api/v1/media/sign?'.$query)->assertOk();
        $this->getJson('/api/v1/media/sign?'.$query)->assertStatus(429);
    }
}

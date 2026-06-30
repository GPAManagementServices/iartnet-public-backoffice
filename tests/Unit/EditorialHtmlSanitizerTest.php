<?php

namespace Tests\Unit;

use App\Support\EditorialHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class EditorialHtmlSanitizerTest extends TestCase
{
    private EditorialHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new EditorialHtmlSanitizer;
    }

    public function test_allows_editorial_markup(): void
    {
        $input = '<p>Hello <strong>world</strong></p><ul><li>One</li></ul>';
        $output = $this->sanitizer->sanitize($input);

        $this->assertStringContainsString('<p>', $output ?? '');
        $this->assertStringContainsString('<strong>world</strong>', $output ?? '');
        $this->assertStringContainsString('<ul>', $output ?? '');
    }

    public function test_strips_script_tags(): void
    {
        $input = '<p>Safe</p><script>alert(1)</script>';
        $output = $this->sanitizer->sanitize($input);

        $this->assertStringNotContainsString('<script', strtolower($output ?? ''));
        $this->assertStringContainsString('Safe', $output ?? '');
    }

    public function test_strips_iframe(): void
    {
        $input = '<iframe src="https://example.org"></iframe><p>Text</p>';
        $output = $this->sanitizer->sanitize($input);

        $this->assertStringNotContainsString('<iframe', strtolower($output ?? ''));
    }

    public function test_removes_javascript_links(): void
    {
        $input = '<a href="javascript:alert(1)">x</a>';
        $output = $this->sanitizer->sanitize($input);

        $this->assertStringNotContainsString('javascript:', strtolower($output ?? ''));
    }

    public function test_is_idempotent(): void
    {
        $input = '<p>Hello <em>there</em></p>';
        $once = $this->sanitizer->sanitize($input);
        $twice = $this->sanitizer->sanitizeIdempotent($input);

        $this->assertSame($once, $twice);
    }

    public function test_adds_rel_on_target_blank(): void
    {
        $input = '<a href="https://example.com" target="_blank">Link</a>';
        $output = $this->sanitizer->sanitize($input);

        $this->assertStringContainsString('rel="noopener noreferrer"', $output ?? '');
    }
}

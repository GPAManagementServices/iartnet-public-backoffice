<?php

namespace Tests\Unit;

use App\Support\HttpExternalUrl;
use PHPUnit\Framework\TestCase;

class HttpExternalUrlTest extends TestCase
{
    public function test_accepts_https_url(): void
    {
        $this->assertSame(
            'https://example.com/path',
            HttpExternalUrl::normalizeForStorage('https://example.com/path')
        );
    }

    public function test_prefixes_bare_hostname(): void
    {
        $this->assertSame(
            'https://example.org',
            HttpExternalUrl::normalizeForStorage('example.org')
        );
    }

    public function test_rejects_javascript_scheme(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage('javascript:alert(1)'));
    }

    public function test_rejects_data_scheme(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage('data:text/html,<script>'));
    }

    public function test_rejects_mailto(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage('mailto:test@example.com'));
    }

    public function test_rejects_userinfo(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage('https://user:pass@example.com'));
    }

    public function test_rejects_localhost(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage('https://localhost'));
    }

    public function test_rejects_crlf(): void
    {
        $this->assertNull(HttpExternalUrl::normalizeForStorage("https://example.com\r\n/evil"));
    }
}

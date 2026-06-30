<?php

namespace Tests\Unit;

use App\Support\ActivityVideoUrls;
use PHPUnit\Framework\TestCase;

class ActivityVideoUrlsTest extends TestCase
{
    public function test_legacy_scalar_to_json_array(): void
    {
        $this->assertNull(ActivityVideoUrls::legacyScalarToJsonArray(null));
        $this->assertNull(ActivityVideoUrls::legacyScalarToJsonArray(''));
        $this->assertSame(
            ['https://example.com/v1'],
            ActivityVideoUrls::legacyScalarToJsonArray('https://example.com/v1')
        );
        $this->assertSame(
            ['https://example.com/v'],
            ActivityVideoUrls::legacyScalarToJsonArray('  https://example.com/v  ')
        );
    }

    public function test_normalize_for_storage_trims_dedupes_and_null_if_empty(): void
    {
        $this->assertNull(ActivityVideoUrls::normalizeForStorage(null));
        $this->assertNull(ActivityVideoUrls::normalizeForStorage([]));

        $this->assertSame(
            ['https://a.test/x', 'https://b.test/y'],
            ActivityVideoUrls::normalizeForStorage([
                '  https://a.test/x  ',
                'https://b.test/y',
                'https://a.test/x',
            ])
        );
    }

    public function test_first_or_null(): void
    {
        $this->assertNull(ActivityVideoUrls::firstOrNull(null));
        $this->assertNull(ActivityVideoUrls::firstOrNull([]));
        $this->assertSame('https://a', ActivityVideoUrls::firstOrNull(['https://a', 'https://b']));
    }

    public function test_normalize_repeater_form_state_flat_and_repeater_rows(): void
    {
        $this->assertNull(ActivityVideoUrls::normalizeRepeaterFormState(null));
        $this->assertNull(ActivityVideoUrls::normalizeRepeaterFormState([]));

        $this->assertSame(
            ['https://a.example/x', 'https://b.example/y'],
            ActivityVideoUrls::normalizeRepeaterFormState([
                ['url' => '  https://a.example/x  '],
                ['url' => 'https://b.example/y'],
            ])
        );

        $this->assertSame(
            ['https://a.example/x', 'https://b.example/y'],
            ActivityVideoUrls::normalizeRepeaterFormState([
                'https://a.example/x',
                ['url' => 'https://b.example/y'],
            ])
        );
    }
}

<?php

namespace Tests\Unit;

use App\Support\TextNormalizer;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase
{
    public function test_trims_ascii_whitespace(): void
    {
        $this->assertSame('hello', TextNormalizer::normalize(" \t\n\r\0\x0Bhello "));
    }

    public function test_trims_nbsp_and_unicode_space_at_edges(): void
    {
        $nbsp = "\xC2\xA0";
        $this->assertSame('x', TextNormalizer::normalize($nbsp.'x'.$nbsp));
    }

    public function test_idempotent(): void
    {
        $s = "  foo  \t";
        $once = TextNormalizer::normalize($s);
        $twice = TextNormalizer::normalize($once);
        $this->assertSame($once, $twice);
    }

    public function test_normalize_null_returns_null(): void
    {
        $this->assertNull(TextNormalizer::normalize(null));
    }

    public function test_normalize_optional_maps_blank_to_null(): void
    {
        $this->assertNull(TextNormalizer::normalizeOptional('   '));
        $this->assertNull(TextNormalizer::normalizeOptional(null));
        $this->assertSame('a', TextNormalizer::normalizeOptional(' a '));
    }

    public function test_normalize_array_leaves_only_strings(): void
    {
        $in = [
            'id' => 5,
            'label' => ['en' => '  hi  ', 'it' => "\t"],
            'people_ids' => [1, 2],
        ];
        $out = TextNormalizer::normalizeArrayLeaves($in);
        $this->assertSame(5, $out['id']);
        $this->assertSame('hi', $out['label']['en']);
        $this->assertSame('', $out['label']['it']);
        $this->assertSame([1, 2], $out['people_ids']);
    }
}

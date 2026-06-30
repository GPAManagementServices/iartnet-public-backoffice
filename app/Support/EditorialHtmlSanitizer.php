<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Centralized HTML sanitization for editorial rich text (People shortbio, etc.).
 */
final class EditorialHtmlSanitizer
{
    private readonly HtmlSanitizerInterface $sanitizer;

    public function __construct(?HtmlSanitizerInterface $sanitizer = null)
    {
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer($this->buildConfig());
    }

    public function sanitize(?string $html): ?string
    {
        if (! is_string($html)) {
            return $html;
        }

        $trimmed = trim($html);
        if ($trimmed === '') {
            return $html;
        }

        $clean = $this->sanitizer->sanitize($trimmed);

        return $this->hardenAnchorLinks($clean);
    }

    /**
     * Idempotent: sanitize(sanitize(x)) === sanitize(x).
     */
    public function sanitizeIdempotent(?string $html): ?string
    {
        $once = $this->sanitize($html);

        if ($once === null || $once === '') {
            return $once;
        }

        return $this->sanitize($once);
    }

    private function buildConfig(): HtmlSanitizerConfig
    {
        return (new HtmlSanitizerConfig())
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('b')
            ->allowElement('i')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->withMaxInputLength(500_000);
    }

    private function hardenAnchorLinks(string $html): string
    {
        if ($html === '' || stripos($html, '<a') === false) {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a');

        if ($anchors) {
            foreach ($anchors as $anchor) {
                if (! $anchor instanceof DOMElement) {
                    continue;
                }

                if (strtolower($anchor->getAttribute('target')) === '_blank') {
                    $anchor->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        $root = $dom->getElementById('__root__');
        if (! $root) {
            return $html;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return preg_replace('/^<\?xml.+?\?>/i', '', $out) ?? $html;
    }
}

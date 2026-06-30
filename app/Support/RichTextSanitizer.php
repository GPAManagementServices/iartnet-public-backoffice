<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

class RichTextSanitizer
{
    /**
     * Rimuove wrapper/link/caption delle immagini inserite da Trix (Filament RichEditor),
     * lasciando solo <img ...>.
     */
    public static function stripTrixAttachmentLinks(?string $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="UTF-8"><div id="__root__">'.$html.'</div>';

        $dom->loadHTML(
            $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);
        $figures = $xpath->query('//figure[@data-trix-attachment]');

        if ($figures) {
            foreach ($figures as $figure) {
                $img = $xpath->query('.//img', $figure)?->item(0);

                if (! $img) {
                    $figure->parentNode?->removeChild($figure);

                    continue;
                }

                $newImg = $img->cloneNode(true);
                $figure->parentNode?->replaceChild($newImg, $figure);
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

        $out = preg_replace('/^<\?xml.+?\?>/i', '', $out);

        return $out;
    }

    /**
     * Versione comoda per un array di translations (tipo Spatie translatable).
     */
    public static function stripTrixAttachmentLinksFromTranslations(array $translations): array
    {
        foreach ($translations as $locale => $value) {
            $translations[$locale] = self::stripTrixAttachmentLinks($value);
        }

        return $translations;
    }
}

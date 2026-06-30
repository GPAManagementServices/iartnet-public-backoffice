<?php

namespace App\Support;

/**
 * Normalizzazione URL video per Activity (lista piatta) e migrazione da video_url legacy.
 */
final class ActivityVideoUrls
{
    /**
     * Migrazione dati: un singolo URL legacy → array JSON o null.
     *
     * @return list<string>|null
     */
    public static function legacyScalarToJsonArray(?string $videoUrl): ?array
    {
        $n = TextNormalizer::normalizeOptional($videoUrl);

        return $n === null ? null : [$n];
    }

    /**
     * Normalizza l'array persistito: trim, rimuove vuoti, deduplica, ordine preservato.
     * Array vuoto → null (coerente con altri JSON opzionali nel progetto).
     *
     * @param  list<string>|list<mixed>|null  $raw
     * @return list<string>|null
     */
    public static function normalizeForStorage(mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        if (! is_array($raw)) {
            return null;
        }

        $out = [];
        $seen = [];

        foreach ($raw as $item) {
            if (! is_string($item)) {
                continue;
            }

            $n = TextNormalizer::normalizeOptional($item);
            if ($n === null) {
                continue;
            }

            if (isset($seen[$n])) {
                continue;
            }

            $seen[$n] = true;
            $out[] = $n;
        }

        return $out === [] ? null : $out;
    }

    /**
     * Converte lo stato del form Filament (Repeater con righe `['url' => string]`, eventualmente
     * mescolato con stringhe per limitazioni di dehydration del Repeater) in lista piatta per il DB.
     *
     * @return list<string>|null
     */
    public static function normalizeRepeaterFormState(mixed $state): ?array
    {
        if ($state === null || $state === []) {
            return null;
        }

        if (! is_array($state)) {
            return null;
        }

        $candidates = [];

        foreach ($state as $item) {
            if (is_string($item)) {
                $candidates[] = $item;

                continue;
            }

            if (is_array($item) && isset($item['url']) && is_string($item['url'])) {
                $candidates[] = $item['url'];
            }
        }

        return self::normalizeForStorage($candidates);
    }

    /**
     * Primo URL per compatibilità API legacy, o null.
     *
     * @param  list<string>|null  $urls
     */
    public static function firstOrNull(?array $urls): ?string
    {
        if ($urls === null || $urls === []) {
            return null;
        }

        return $urls[0] ?? null;
    }
}

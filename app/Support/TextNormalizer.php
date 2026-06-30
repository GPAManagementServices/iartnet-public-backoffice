<?php

namespace App\Support;

/**
 * Normalizzazione testo in salvataggio: trim ASCII (PHP) + rimozione separatori Unicode (Zs) ai bordi.
 * Idempotente. Non altera strutture JSON: usare normalizeArrayLeaves solo su array noti.
 */
final class TextNormalizer
{
    /**
     * Normalizza una stringa (o null). null resta null.
     * Passo 1: trim() nativo (spazio, tab, CR, LF, VT, NUL).
     * Passo 2: rimozione ripetuta di \p{Zs} a inizio/fine (include NBSP e altri spazi Unicode).
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = trim($value);

        do {
            $previous = $s;
            $s = preg_replace('/^\p{Zs}+/u', '', $s) ?? '';
            $s = preg_replace('/\p{Zs}+$/u', '', $s) ?? '';
        } while ($s !== $previous);

        return $s;
    }

    /**
     * Come normalize(), ma stringa vuota → null (campi opzionali scalari).
     */
    public static function normalizeOptional(?string $value): ?string
    {
        $n = self::normalize($value);

        return ($n === null || $n === '') ? null : $n;
    }

    /**
     * Normalizza solo valori stringa nelle foglie; array ricorsivi; chiavi e non-stringhe invariati.
     */
    public static function normalizeArrayLeaves(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::normalize($value) ?? '';
        }

        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            $out[$key] = self::normalizeArrayLeaves($item);
        }

        return $out;
    }
}

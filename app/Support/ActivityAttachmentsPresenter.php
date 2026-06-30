<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Serializza gli allegati activity per l'API (contratto ActivityAttachment[], allineato al frontend Nuxt).
 *
 * @phpstan-type StoredAttachment array{path?: string, title?: string|null, mime_type?: string|null}
 */
final class ActivityAttachmentsPresenter
{
    /**
     * @param  list<StoredAttachment>|list<string>|null  $attachments
     * @return list<array{id: int, url: string, path: string, title: ?string, mimeType: string}>
     */
    public static function forApi(?array $attachments, string $diskName = 'public'): array
    {
        if ($attachments === null || $attachments === []) {
            return [];
        }

        $disk = Storage::disk($diskName);
        $out = [];
        $id = 1;

        foreach ($attachments as $item) {
            if (is_string($item)) {
                $path = $item;
                $title = null;
                $storedMime = null;
            } elseif (is_array($item)) {
                $path = $item['path'] ?? null;
                $rawTitle = $item['title'] ?? null;
                $title = is_string($rawTitle) && $rawTitle !== '' ? $rawTitle : null;
                $storedMime = $item['mime_type'] ?? null;
                if ($storedMime !== null && ! is_string($storedMime)) {
                    $storedMime = null;
                }
            } else {
                continue;
            }

            if (! is_string($path) || $path === '') {
                continue;
            }

            $out[] = [
                'id' => $id,
                'url' => self::absolutePublicUrl($disk, $path),
                'path' => $path,
                'title' => $title,
                'mimeType' => self::resolveMimeType($path, $storedMime, $disk),
            ];
            $id++;
        }

        return $out;
    }

    /**
     * Garantisce URL assoluta (browser) anche se il disk espone solo un path relativo.
     */
    public static function absolutePublicUrl(Cloud $disk, string $path): string
    {
        $url = $disk->url($path);
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $base = rtrim((string) config('app.url'), '/');

        return $base.'/'.ltrim($url, '/');
    }

    /**
     * Sempre valorizzato (contratto frontend: filtro PDF su application/pdf).
     */
    public static function resolveMimeType(string $path, ?string $storedMime, Filesystem $disk): string
    {
        if (is_string($storedMime) && $storedMime !== '') {
            return $storedMime;
        }

        if ($disk->exists($path)) {
            $detected = @mime_content_type($disk->path($path));
            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }

        return self::mimeFromExtension($path) ?? 'application/octet-stream';
    }

    private static function mimeFromExtension(string $path): ?string
    {
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain',
            'json' => 'application/json',
            default => null,
        };
    }
}

<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Serializza la destinazione pubblica di un item Press (file o URL esterno).
 *
 * @phpstan-type PressDestinationExternal array{type: 'external', url: string}
 * @phpstan-type PressDestinationFile array{type: 'file', url: string, mimeType: string, fileName: string, fileSize: int}
 */
final class PressDestinationPresenter
{
    /**
     * @return PressDestinationExternal|PressDestinationFile|null
     */
    public static function forApi(
        ?string $destinationType,
        ?string $filePath,
        ?string $externalUrl,
        string $diskName = 'public',
    ): ?array {
        $type = is_string($destinationType) ? trim($destinationType) : '';

        if ($type === '' || $type === 'none') {
            return null;
        }

        if ($type === 'external') {
            $url = is_string($externalUrl) ? trim($externalUrl) : '';
            if ($url === '') {
                return null;
            }

            return [
                'type' => 'external',
                'url' => $url,
            ];
        }

        if ($type === 'file') {
            $path = is_string($filePath) ? trim($filePath) : '';
            if ($path === '') {
                return null;
            }

            $disk = Storage::disk($diskName);

            return [
                'type' => 'file',
                'url' => ActivityAttachmentsPresenter::absolutePublicUrl($disk, $path),
                'mimeType' => ActivityAttachmentsPresenter::resolveMimeType($path, null, $disk),
                'fileName' => basename($path),
                'fileSize' => self::fileSize($disk, $path),
            ];
        }

        return null;
    }

    private static function fileSize(Filesystem $disk, string $path): int
    {
        try {
            return $disk->exists($path) ? (int) $disk->size($path) : 0;
        }
        catch (\Throwable) {
            return 0;
        }
    }
}

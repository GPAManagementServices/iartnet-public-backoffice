<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allinea le FK create verso `curator_media` (inesistente: Curator usa `media`) su DB già popolati.
 * Esegue solo su MySQL / MariaDB. Non elimina dati; richiede che gli ID referenziati esistano in `media`.
 */
return new class extends Migration
{
    /** @var list<array{0: string, 1: list<string>}> */
    private const TARGETS = [
        ['projects', ['opengraph_picture_id']],
        ['faqs', ['opengraph_picture_id']],
        ['research_catalogues', ['opengraph_picture_id', 'cover_image_id']],
    ];

    public function up(): void
    {
        if (! $this->isMysqlFamily()) {
            return;
        }

        if (! Schema::hasTable('media')) {
            return;
        }

        foreach (self::TARGETS as [$table, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->dropForeignKeyIfExists($table, $column);

                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->foreign($column)
                        ->references('id')
                        ->on('media')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (! $this->isMysqlFamily()) {
            return;
        }

        $canRestoreCurator = Schema::hasTable('curator_media');

        foreach (self::TARGETS as [$table, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->dropForeignKeyIfExists($table, $column);

                if ($canRestoreCurator) {
                    Schema::table($table, function (Blueprint $blueprint) use ($column) {
                        $blueprint->foreign($column)
                            ->references('id')
                            ->on('curator_media')
                            ->nullOnDelete();
                    });
                }
            }
        }
    }

    private function isMysqlFamily(): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        return $driver === 'mysql' || $driver === 'mariadb';
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        } catch (\Throwable) {
            // Nessuna FK su questa colonna (DB già corretto o stato manuale).
        }
    }
};

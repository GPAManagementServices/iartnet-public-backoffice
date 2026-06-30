<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $row = DB::selectOne('
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                LIMIT 1
            ', [$table, $indexName]);

            return (bool) $row;
        }
        $quoted = '"'.str_replace('"', '""', $table).'"';
        $rows = DB::select("PRAGMA index_list({$quoted})");

        return collect($rows)->contains('name', $indexName);
    }

    public function up(): void
    {
        if (! Schema::hasColumn('institutions', 'slug_it')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->string('slug_it')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('institutions', 'slug_en')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->string('slug_en')->nullable()->index();
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE institutions SET slug_it = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.it')), ''), 'null')");
            DB::statement("UPDATE institutions SET slug_en = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')), ''), 'null')");
        } else {
            DB::statement("UPDATE institutions SET slug_it = NULLIF(NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.it'), ''), '\"', '')), ''), 'null')");
            DB::statement("UPDATE institutions SET slug_en = NULLIF(NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.en'), ''), '\"', '')), ''), 'null')");
        }

        if (! $this->indexExists('institutions', 'institutions_slug_it_unique')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->unique('slug_it', 'institutions_slug_it_unique');
            });
        }

        if (! $this->indexExists('institutions', 'institutions_slug_en_unique')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->unique('slug_en', 'institutions_slug_en_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('institutions', 'institutions_slug_it_unique')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->dropUnique('institutions_slug_it_unique');
            });
        }

        if ($this->indexExists('institutions', 'institutions_slug_en_unique')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->dropUnique('institutions_slug_en_unique');
            });
        }

        if (Schema::hasColumn('institutions', 'slug_it') || Schema::hasColumn('institutions', 'slug_en')) {
            Schema::table('institutions', function (Blueprint $table) {
                if (Schema::hasColumn('institutions', 'slug_it')) {
                    $table->dropColumn('slug_it');
                }
                if (Schema::hasColumn('institutions', 'slug_en')) {
                    $table->dropColumn('slug_en');
                }
            });
        }
    }
};

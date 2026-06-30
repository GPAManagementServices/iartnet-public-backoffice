<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('slug_it')->nullable()->index();
            $table->string('slug_en')->nullable()->index();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE people SET slug_it = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.it')), ''), 'null')");
            DB::statement("UPDATE people SET slug_en = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')), ''), 'null')");
        } else {
            DB::statement("UPDATE people SET slug_it = NULLIF(NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.it'), ''), '\"', '')), ''), 'null')");
            DB::statement("UPDATE people SET slug_en = NULLIF(NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.en'), ''), '\"', '')), ''), 'null')");
        }

        Schema::table('people', function (Blueprint $table) {
            $table->unique('slug_it', 'people_slug_it_unique');
            $table->unique('slug_en', 'people_slug_en_unique');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique('people_slug_it_unique');
            $table->dropUnique('people_slug_en_unique');
            $table->dropColumn(['slug_it', 'slug_en']);
        });
    }
};

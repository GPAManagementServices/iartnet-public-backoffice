<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('slug_it')->nullable()->index();
            $table->string('slug_en')->nullable()->index();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE activities SET slug_it = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.it')), '')");
            DB::statement("UPDATE activities SET slug_en = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')), '')");
        } else {
            DB::statement("UPDATE activities SET slug_it = NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.it'), ''), '\"', '')), '')");
            DB::statement("UPDATE activities SET slug_en = NULLIF(TRIM(REPLACE(COALESCE(json_extract(slug, '$.en'), ''), '\"', '')), '')");
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->unique('slug_it', 'activities_slug_it_unique');
            $table->unique('slug_en', 'activities_slug_en_unique');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropUnique('activities_slug_it_unique');
            $table->dropUnique('activities_slug_en_unique');
            $table->dropColumn(['slug_it', 'slug_en']);
        });
    }
};

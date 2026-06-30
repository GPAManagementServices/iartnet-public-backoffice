<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE people SET slug = JSON_OBJECT('en', slug) WHERE slug IS NOT NULL AND slug != ''");
        } else {
            DB::statement("UPDATE people SET slug = json_object('en', slug) WHERE slug IS NOT NULL AND slug != ''");
        }

        Schema::table('people', function (Blueprint $table) {
            $table->json('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE people SET slug = JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')) WHERE JSON_VALID(slug)");
        } else {
            DB::statement("UPDATE people SET slug = json_extract(slug, '$.en') WHERE json_extract(slug, '$.en') IS NOT NULL");
        }
    }
};

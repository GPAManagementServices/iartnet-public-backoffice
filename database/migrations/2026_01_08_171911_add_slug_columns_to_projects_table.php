<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug_it')->nullable()->unique()->after('slug');
            $table->string('slug_en')->nullable()->unique()->after('slug_it');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['slug_it']);
            $table->dropUnique(['slug_en']);
            $table->dropColumn(['slug_it', 'slug_en']);
        });
    }
};

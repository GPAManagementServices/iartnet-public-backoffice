<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // alt translatable per cover
            $table->json('cover_image_alt')->nullable()->after('cover_image_id');

            // alt per gallery (mappa: media_id => {it: "...", en: "..."})
            $table->json('gallery_alt')->nullable()->after('gallery');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['cover_image_alt', 'gallery_alt']);
        });
    }
};

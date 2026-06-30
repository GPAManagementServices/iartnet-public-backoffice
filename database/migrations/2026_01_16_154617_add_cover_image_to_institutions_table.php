<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->unsignedBigInteger('cover_image_id')->nullable()->after('opengraph_picture_id'); // scegli l'after giusto
            $table->json('cover_image_alt')->nullable()->after('cover_image_id');

            // Curator di solito usa la tabella "media"
            $table->foreign('cover_image_id')->references('id')->on('media')->nullOnDelete();

            $table->index('cover_image_id');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropForeign(['cover_image_id']);
            $table->dropIndex(['cover_image_id']);
            $table->dropColumn(['cover_image_id', 'cover_image_alt']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Translatable
            $table->json('title');
            $table->json('description')->nullable();

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->json('opengraph_title')->nullable();
            $table->json('opengraph_description')->nullable();

            // Curator images (nel tuo DB la tabella è "media", non "curator_media")
            $table->foreignId('opengraph_picture_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('opengraph_picture_alt')->nullable();

            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();

            $table->string('status')->default('draft');

            $table->string('slug_it')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

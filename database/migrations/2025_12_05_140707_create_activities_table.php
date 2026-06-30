<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('title');                 // translatable
            $table->string('slug')->unique();
            $table->string('status')->default('draft');

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('opengraph_title')->nullable();
            $table->json('opengraph_description')->nullable();

            $table->foreignId('opengraph_picture_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('opengraph_picture_alt')->nullable();

            $table->json('subtitle')->nullable();
            $table->json('institutions')->nullable(); // testo semplice o elenco
            $table->json('description')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('people')->nullable();
            $table->json('gallery')->nullable();     // array di media IDs
            $table->string('video_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

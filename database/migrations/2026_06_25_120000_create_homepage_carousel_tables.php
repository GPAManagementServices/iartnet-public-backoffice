<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Support\HomepageCanonicalImporter;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_carousel_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title');
            $table->string('digital_object_slug')->unique();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->index(['is_published', 'sort_order', 'id']);
        });

        Schema::create('homepage_highlight_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('title_variant')->default('author_title_subtitle');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('subtitle_1')->nullable();
            $table->string('subtitle_2')->nullable();
            $table->text('description')->nullable();
            $table->string('digital_object_slug')->unique();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('cover_iiif_identifier')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->index(['is_published', 'sort_order', 'id']);
        });

        (new HomepageCanonicalImporter())->import();
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_highlight_items');
        Schema::dropIfExists('hero_carousel_items');
    }

};

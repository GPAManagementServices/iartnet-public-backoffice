<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('press_pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('singleton_key')->default('default')->unique();
            $table->string('status')->default('draft');

            $table->string('title');
            $table->text('intro')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('opengraph_title')->nullable();
            $table->text('opengraph_description')->nullable();
            $table->foreignId('opengraph_picture_id')->nullable()->constrained('media')->nullOnDelete();
        });

        Schema::create('press_contacts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('press_page_id')->constrained('press_pages')->cascadeOnDelete();
            $table->string('label');
            $table->string('email');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('published');

            $table->index(['press_page_id', 'status', 'sort_order', 'id']);
        });

        Schema::create('press_releases', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('press_page_id')->constrained('press_pages')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('cover_image_alt')->nullable();

            $table->string('destination_type')->default('none');
            $table->string('file_path')->nullable();
            $table->string('external_url', 2048)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('published');

            $table->index(['press_page_id', 'status', 'sort_order', 'id']);
        });

        Schema::create('press_documents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('press_page_id')->constrained('press_pages')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->date('date')->nullable();

            $table->string('destination_type')->default('none');
            $table->string('file_path')->nullable();
            $table->string('external_url', 2048)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('published');

            $table->index(['press_page_id', 'status', 'sort_order', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('press_documents');
        Schema::dropIfExists('press_releases');
        Schema::dropIfExists('press_contacts');
        Schema::dropIfExists('press_pages');
    }
};

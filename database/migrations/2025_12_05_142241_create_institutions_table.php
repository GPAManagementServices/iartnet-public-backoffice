<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('name');                    // traducibile
            $table->string('slug')->unique();
            $table->string('status')->default('draft');

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('opengraph_title')->nullable();
            $table->json('opengraph_description')->nullable();

            $table->foreignId('opengraph_picture_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('opengraph_picture_alt')->nullable(); // traducibile

            $table->json('description')->nullable(); // traducibile

            $table->json('people')->nullable();      // ARRAY di IDs di people
            $table->foreignId('logo_image_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};

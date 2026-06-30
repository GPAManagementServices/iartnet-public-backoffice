<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('type');      // 'activity', 'institution', 'person'
            $table->json('name');
            $table->json('slug');
            $table->string('status')->default('draft');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->index(['type']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('institutions');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('people');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('institutions');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('categories');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('categories');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('categories');
        });

        Schema::dropIfExists('categories');
    }
};

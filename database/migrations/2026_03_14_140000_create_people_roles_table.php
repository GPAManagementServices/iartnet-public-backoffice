<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name_en', 255);
            $table->string('name_it', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_roles');
    }
};

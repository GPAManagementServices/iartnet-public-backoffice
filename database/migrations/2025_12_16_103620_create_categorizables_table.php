<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorizables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('categorizable_id');
            $table->string('categorizable_type');

            $table->timestamps();

            $table->unique(
                ['category_id', 'categorizable_id', 'categorizable_type'],
                'unique_categorizable'
            );

            $table->index(['categorizable_id', 'categorizable_type'], 'idx_categorizable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorizables');
    }
};

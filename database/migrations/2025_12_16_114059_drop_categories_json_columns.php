<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'categories')) {
                $table->dropColumn('categories');
            }
        });

        Schema::table('institutions', function (Blueprint $table) {
            if (Schema::hasColumn('institutions', 'categories')) {
                $table->dropColumn('categories');
            }
        });

        Schema::table('people', function (Blueprint $table) {
            if (Schema::hasColumn('people', 'categories')) {
                $table->dropColumn('categories');
            }
        });
    }

    public function down(): void
    {
        // se vuoi ripristinare: aggiungi json nullable (ma non ripopola i dati)
        // Schema::table(...)->json('categories')->nullable();
    }
};

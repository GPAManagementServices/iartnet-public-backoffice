<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
            $table->renameColumn('surname', 'last_name');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->renameColumn('first_name', 'name');
            $table->renameColumn('last_name', 'surname');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // slug da string a json (per traduzioni)
            $table->json('slug')->change();

            // nuovo attachment (un solo file, salviamo il path)
            $table->string('attachment')->nullable()->after('video_url');

            // date & time
            $table->date('start_date')->nullable()->after('attachment');
            $table->time('start_hour')->nullable()->after('start_date');
            $table->date('end_date')->nullable()->after('start_hour');
            $table->time('end_hour')->nullable()->after('end_date');

            // location testuale ma traducibile → json
            $table->json('location')->nullable()->after('end_hour');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // ATTENZIONE: qui devi decidere come tornare indietro.
            // Esempio (se vuoi davvero il rollback):
            $table->string('slug')->change();
            $table->dropColumn([
                'attachment',
                'start_date',
                'start_hour',
                'end_date',
                'end_hour',
                'location',
            ]);
        });
    }
};

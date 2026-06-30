<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workaround brownfield: se la tabella `projects` esiste già (es. database popolato da import
 * SQL o tabella `migrations` non allineata allo schema reale), `up()` non esegue il CREATE
 * così `php artisan migrate` non fallisce con SQLSTATE 1050 (table already exists).
 *
 * Prima di affidarsi allo skip: verificare che lo schema esistente sia compatibile con
 * quanto definito sotto; divergenze vanno corrette con migration dedicate.
 *
 * Rollback: `down()` usa ancora `dropIfExists`. Su ambienti dove `up()` è stato saltato
 * perché la tabella preesisteva, un `migrate:rollback` su questo batch eliminerebbe comunque
 * `projects` — evitare rollback ciechi in produzione.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('title'); // translatable
            $table->string('slug')->unique();

            $table->string('status')->default('draft'); // draft|published|archived (esempio)

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->json('opengraph_title')->nullable();
            $table->json('opengraph_description')->nullable();

            // Curator: tabella effettiva `media` (non `curator_media`)
            $table->foreignId('opengraph_picture_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('opengraph_picture_alt')->nullable();

            $table->json('description')->nullable();

            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

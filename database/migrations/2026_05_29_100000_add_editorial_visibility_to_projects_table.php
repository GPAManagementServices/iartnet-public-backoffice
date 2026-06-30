<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'show_in_homepage')) {
                $table->boolean('show_in_homepage')->default(false);
            }
            if (! Schema::hasColumn('projects', 'homepage_order')) {
                $table->unsignedInteger('homepage_order')->nullable();
            }
            if (! Schema::hasColumn('projects', 'show_in_projects')) {
                $table->boolean('show_in_projects')->default(true);
            }
            if (! Schema::hasColumn('projects', 'projects_order')) {
                $table->unsignedInteger('projects_order')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $columns = ['show_in_homepage', 'homepage_order', 'show_in_projects', 'projects_order'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

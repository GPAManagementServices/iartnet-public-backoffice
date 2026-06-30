<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('video_url');
        });

        $driver = DB::getDriverName();
        $query = DB::table('activities')->whereNotNull('attachment');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $query->where('attachment', '!=', '');
        } else {
            $query->where('attachment', '<>', '');
        }

        foreach ($query->cursor() as $row) {
            $path = $row->attachment;
            if (! is_string($path) || $path === '') {
                continue;
            }

            DB::table('activities')->where('id', $row->id)->update([
                'attachments' => json_encode([['path' => $path]]),
            ]);
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('video_url');
        });

        foreach (DB::table('activities')->whereNotNull('attachments')->cursor() as $row) {
            $decoded = json_decode($row->attachments, true);
            $firstPath = null;
            if (is_array($decoded) && $decoded !== []) {
                $first = $decoded[0];
                if (is_string($first)) {
                    $firstPath = $first;
                } elseif (is_array($first) && isset($first['path']) && is_string($first['path'])) {
                    $firstPath = $first['path'];
                }
            }

            DB::table('activities')->where('id', $row->id)->update([
                'attachment' => $firstPath,
                'attachments' => null,
            ]);
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};

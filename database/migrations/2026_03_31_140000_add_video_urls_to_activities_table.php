<?php

use App\Support\ActivityVideoUrls;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('video_urls')->nullable()->after('video_url');
        });

        DB::table('activities')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $legacy = $row->video_url ?? null;
                    $urls = ActivityVideoUrls::legacyScalarToJsonArray(is_string($legacy) ? $legacy : null);

                    DB::table('activities')->where('id', $row->id)->update([
                        'video_urls' => $urls === null ? null : json_encode($urls),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('activities')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $first = null;
                    $raw = $row->video_urls ?? null;
                    if (is_string($raw) && $raw !== '') {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0])) {
                            $first = $decoded[0];
                        }
                    }

                    DB::table('activities')->where('id', $row->id)->update([
                        'video_url' => $first,
                    ]);
                }
            });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('video_urls');
        });
    }
};

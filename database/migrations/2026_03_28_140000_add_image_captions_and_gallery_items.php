<?php

use App\Support\ContentGalleryItems;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('cover_image_caption')->nullable()->after('cover_image_alt');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->json('cover_image_caption')->nullable()->after('cover_image_alt');
        });

        Schema::table('research_catalogues', function (Blueprint $table) {
            $table->json('cover_image_caption')->nullable()->after('cover_image_alt');
        });

        $this->migrateGalleryColumn('activities');
        $this->migrateGalleryColumn('projects');
    }

    public function down(): void
    {
        $this->revertGalleryColumn('activities');
        $this->revertGalleryColumn('projects');

        Schema::table('research_catalogues', function (Blueprint $table) {
            $table->dropColumn('cover_image_caption');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('cover_image_caption');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('cover_image_caption');
        });
    }

    private function migrateGalleryColumn(string $table): void
    {
        DB::table($table)
            ->whereNotNull('gallery')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $raw = json_decode($row->gallery, true);
                    if (! is_array($raw)) {
                        continue;
                    }

                    $items = ContentGalleryItems::normalize($raw);
                    $persisted = ContentGalleryItems::toPersisted($items);

                    DB::table($table)->where('id', $row->id)->update([
                        'gallery' => json_encode($persisted),
                    ]);
                }
            });
    }

    private function revertGalleryColumn(string $table): void
    {
        DB::table($table)
            ->whereNotNull('gallery')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $raw = json_decode($row->gallery, true);
                    if (! is_array($raw)) {
                        continue;
                    }

                    $items = ContentGalleryItems::normalize($raw);
                    $ids = ContentGalleryItems::mediaIds($items);

                    DB::table($table)->where('id', $row->id)->update([
                        'gallery' => json_encode($ids),
                    ]);
                }
            });
    }
};

<?php

use App\Models\Activity;
use App\Models\Institution;
use App\Models\Person;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // --- ACTIVITIES ---
            Activity::query()
                ->select(['id', 'categories'])
                ->whereNotNull('categories')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $activity) {
                        $ids = $this->normalizeIds($activity->categories);

                        if (! empty($ids)) {
                            // scrive sulla pivot usando la relazione morphToMany
                            $activity->categories()->syncWithoutDetaching($ids);
                        }
                    }
                });

            // --- INSTITUTIONS ---
            Institution::query()
                ->select(['id', 'categories'])
                ->whereNotNull('categories')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $institution) {
                        $ids = $this->normalizeIds($institution->categories);

                        if (! empty($ids)) {
                            $institution->categories()->syncWithoutDetaching($ids);
                        }
                    }
                });

            // --- PEOPLE ---
            Person::query()
                ->select(['id', 'categories'])
                ->whereNotNull('categories')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $person) {
                        $ids = $this->normalizeIds($person->categories);

                        if (! empty($ids)) {
                            $person->categories()->syncWithoutDetaching($ids);
                        }
                    }
                });
        });
    }

    public function down(): void
    {
        // Down: non possiamo ricostruire i JSON in modo affidabile (lo lasciamo vuoto).
        // Se vuoi, possiamo anche fare detach, ma di solito è meglio non farlo.
    }

    private function normalizeIds($value): array
    {
        // categories può essere: null, array, json string, collection...
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => is_int($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }
};

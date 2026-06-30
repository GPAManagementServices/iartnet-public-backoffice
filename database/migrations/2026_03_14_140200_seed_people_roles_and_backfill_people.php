<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sort = 0;
        $insert = function (string $slug, string $nameEn, string $nameIt) use (&$sort) {
            $sort += 10;
            DB::table('people_roles')->insert([
                'slug' => $slug,
                'name_en' => $nameEn,
                'name_it' => $nameIt,
                'sort_order' => $sort,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        foreach (config('people_page.global_roles', []) as $slug => $labels) {
            if (! is_array($labels)) {
                continue;
            }
            $en = trim((string) ($labels['en'] ?? ''));
            $it = trim((string) ($labels['it'] ?? ''));
            if ($en === '' || $it === '') {
                continue;
            }
            $insert((string) $slug, $en, $it);
        }

        foreach (config('people_page.dedicated_section_roles', []) as $slug => $labels) {
            if (! is_array($labels)) {
                continue;
            }
            $en = trim((string) ($labels['en'] ?? ''));
            $it = trim((string) ($labels['it'] ?? ''));
            if ($en === '' || $it === '') {
                continue;
            }
            $insert((string) $slug, $en, $it);
        }

        foreach (config('people_page.institution_section_roles', []) as $slug => $labels) {
            if (! is_array($labels)) {
                continue;
            }
            $en = trim((string) ($labels['en'] ?? ''));
            $it = trim((string) ($labels['it'] ?? ''));
            if ($en === '' || $it === '') {
                continue;
            }
            $insert((string) $slug, $en, $it);
        }

        $rolesByEn = DB::table('people_roles')->pluck('id', 'name_en')->all();

        DB::table('people')->orderBy('id')->chunkById(100, function ($rows) use ($rolesByEn) {
            foreach ($rows as $row) {
                $roleJson = $row->role ?? null;
                if ($roleJson === null || $roleJson === '') {
                    continue;
                }
                $decoded = json_decode($roleJson, true);
                if (! is_array($decoded)) {
                    continue;
                }
                $en = isset($decoded['en']) ? trim((string) $decoded['en']) : '';
                if ($en === '' || strtolower($en) === 'null') {
                    continue;
                }
                if (! isset($rolesByEn[$en])) {
                    continue;
                }
                DB::table('people')
                    ->where('id', $row->id)
                    ->whereNull('people_role_id')
                    ->update(['people_role_id' => $rolesByEn[$en]]);
            }
        });

        DB::table('people')->orderBy('id')->chunkById(100, function ($rows) use ($rolesByEn) {
            foreach ($rows as $row) {
                $ir = $row->institution_roles ?? null;
                if ($ir === null || $ir === '') {
                    continue;
                }
                $rowsDecoded = json_decode($ir, true);
                if (! is_array($rowsDecoded)) {
                    continue;
                }
                $changed = false;
                foreach ($rowsDecoded as $i => $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    if (! empty($item['people_role_id'])) {
                        continue;
                    }
                    $en = isset($item['role']['en']) ? trim((string) $item['role']['en']) : '';
                    if ($en === '' || strtolower($en) === 'null') {
                        continue;
                    }
                    if (! isset($rolesByEn[$en])) {
                        continue;
                    }
                    $rowsDecoded[$i]['people_role_id'] = (int) $rolesByEn[$en];
                    $changed = true;
                }
                if ($changed) {
                    DB::table('people')->where('id', $row->id)->update([
                        'institution_roles' => json_encode($rowsDecoded),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('people')->update(['people_role_id' => null]);
        DB::table('people_roles')->delete();
    }
};

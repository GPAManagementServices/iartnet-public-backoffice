<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class HomepageCanonicalImporter
{
    public function __construct(
        private readonly bool $insertMissingMedia = true,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>|null  $heroItems
     * @param  array<int, array<string, mixed>>|null  $highlightItems
     */
    public function import(?array $heroItems = null, ?array $highlightItems = null): void
    {
        $now = now();

        foreach ($heroItems ?? $this->heroItems() as $item) {
            $mediaId = $this->resolveMediaId($item['cover_media_path'], $item['expected_media_id'] ?? null);

            DB::table('hero_carousel_items')->updateOrInsert(
                ['digital_object_slug' => $item['digital_object_slug']],
                [
                    'title' => $item['title'],
                    'cover_media_id' => $mediaId,
                    'sort_order' => $item['sort_order'],
                    'is_published' => $item['is_published'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        foreach ($highlightItems ?? $this->highlightItems() as $item) {
            $mediaId = isset($item['cover_media_path'])
                ? $this->resolveMediaId($item['cover_media_path'])
                : null;

            DB::table('homepage_highlight_items')->updateOrInsert(
                ['digital_object_slug' => $item['digital_object_slug']],
                [
                    'title_variant' => $item['title_variant'],
                    'title' => $item['title'],
                    'author' => $item['author'] ?? null,
                    'subtitle_1' => $item['subtitle_1'] ?? null,
                    'subtitle_2' => $item['subtitle_2'] ?? null,
                    'description' => $item['description'] ?? null,
                    'cover_media_id' => $mediaId,
                    'cover_iiif_identifier' => $item['cover_iiif_identifier'] ?? null,
                    'sort_order' => $item['sort_order'],
                    'is_published' => $item['is_published'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function resolveMediaId(string $path, ?int $expectedId = null): int
    {
        $mediaTable = app(config('curator.model'))->getTable();
        $expectedUuid = $this->uuidStem($path);

        if ($expectedId !== null) {
            $media = DB::table($mediaTable)->where('id', $expectedId)->first();

            if ($media) {
                $actualUuid = $this->uuidStem((string) $media->path);
                if ($actualUuid !== $expectedUuid) {
                    throw new RuntimeException("Homepage carousel media mismatch for media.id {$expectedId}: expected {$expectedUuid}, found {$actualUuid}.");
                }

                return $expectedId;
            }

            $collision = DB::table($mediaTable)
                ->where('path', 'like', '%'.$expectedUuid.'%')
                ->first();

            if ($collision) {
                throw new RuntimeException("Homepage carousel media collision for {$expectedUuid}: expected media.id {$expectedId}, found media.id {$collision->id}.");
            }

            if (! $this->insertMissingMedia) {
                throw new RuntimeException("Homepage carousel required media missing for media.id {$expectedId} and UUID {$expectedUuid}.");
            }

            $this->insertMedia($path, $expectedId);

            return $expectedId;
        }

        $media = DB::table($mediaTable)->where('path', $path)->first();
        if ($media) {
            return (int) $media->id;
        }

        $matches = DB::table($mediaTable)
            ->where('path', 'like', '%'.$expectedUuid.'%')
            ->get();

        if ($matches->count() > 1) {
            throw new RuntimeException("Homepage carousel media collision for UUID {$expectedUuid}: found {$matches->count()} media records.");
        }

        if ($matches->count() === 1) {
            return (int) $matches->first()->id;
        }

        if (! $this->insertMissingMedia) {
            throw new RuntimeException("Homepage carousel required media missing for UUID {$expectedUuid}.");
        }

        return $this->insertMedia($path);
    }

    private function insertMedia(string $path, ?int $id = null): int
    {
        $mediaTable = app(config('curator.model'))->getTable();
        $now = now();
        $name = basename($path);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $directory = trim(dirname($path), '.\\/');

        $payload = [
            'disk' => 'public',
            'directory' => $directory === '' ? 'media' : $directory,
            'visibility' => 'public',
            'name' => $name,
            'path' => $path,
            'width' => null,
            'height' => null,
            'size' => null,
            'type' => 'image',
            'ext' => $ext,
            'alt' => null,
            'title' => null,
            'description' => null,
            'caption' => null,
            'exif' => null,
            'curations' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($id !== null) {
            $payload['id'] = $id;
        }

        DB::table($mediaTable)->insert($payload);

        return $id ?? (int) DB::getPdo()->lastInsertId();
    }

    private function uuidStem(string $path): string
    {
        return Str::of(basename($path))
            ->beforeLast('.')
            ->lower()
            ->toString();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function heroItems(): array
    {
        return [
            ['title' => 'Bagatella_2a', 'digital_object_slug' => 'LO12124798', 'sort_order' => 0, 'is_published' => true, 'cover_media_path' => 'media/959c418f-3ea3-4469-bff1-91f07a29408a.jpg', 'expected_media_id' => 649],
            ['title' => 'Le cascate di Tivoli', 'digital_object_slug' => 'OA_4t010-00050', 'sort_order' => 1, 'is_published' => true, 'cover_media_path' => 'media/e10f7545-0734-4da1-8cf4-ae794c637eff.webp'],
            ['title' => 'Bellagio', 'digital_object_slug' => 'MIDFICCD_MIDF_7835154352771AI652S303AI652BP02_SC17_0912026_03_03', 'sort_order' => 2, 'is_published' => true, 'cover_media_path' => 'media/8087ad91-c589-42b1-88ee-1f512dc5b9f1.webp'],
            ['title' => 'Il Flauto Magico', 'digital_object_slug' => '2015-2016-2483', 'sort_order' => 3, 'is_published' => true, 'cover_media_path' => 'media/45441287-3c69-42fa-b6ca-7affafca2289.webp'],
            ['title' => 'Angeli', 'digital_object_slug' => 'D_4t060-01743', 'sort_order' => 4, 'is_published' => true, 'cover_media_path' => 'media/8d546420-a903-4389-a63b-953cabc17710.webp'],
            ['title' => 'Cupido e una Musa', 'digital_object_slug' => 'D_4t060-01833', 'sort_order' => 5, 'is_published' => true, 'cover_media_path' => 'media/df63f7de-e99c-4a50-85f3-d8245a014f12.webp'],
            ['title' => "Madonna dell'Aspo", 'digital_object_slug' => 'OA_4t010-00009', 'sort_order' => 6, 'is_published' => true, 'cover_media_path' => 'media/851216b1-a709-4d41-919b-6b882713404e.webp'],
            ['title' => 'Testa femminile ideale', 'digital_object_slug' => 'D_4t060-01852', 'sort_order' => 7, 'is_published' => true, 'cover_media_path' => 'media/16c4d7cd-4d7e-4136-9e6d-d0e68fc5c0cc.webp'],
            ['title' => "Ruderi della Basilica di Sant'Eufemia sull'Isola Comacina", 'digital_object_slug' => 'MIDFICCD_MIDF_9584246109671AI652S303AI652F03384', 'sort_order' => 8, 'is_published' => true, 'cover_media_path' => 'media/bea86a16-0ff8-4202-a9b8-ae086b97350c.webp'],
            ['title' => 'La congiura dei Lampugnani', 'digital_object_slug' => 'OA_4t020-00030', 'sort_order' => 9, 'is_published' => true, 'cover_media_path' => 'media/2418d8a4-2ade-411c-824c-bb418cde1d4f.webp'],
            ['title' => 'Disfida di Barletta', 'digital_object_slug' => 'DI0900390230S39S39', 'sort_order' => 10, 'is_published' => true, 'cover_media_path' => 'media/23bbc472-9fce-456a-b9fb-002953ea8847.webp'],
            ['title' => 'Cenerentola', 'digital_object_slug' => '2024-2025-2410', 'sort_order' => 11, 'is_published' => true, 'cover_media_path' => 'media/f089adf9-65d7-49fc-a1fc-c6a9164e0f6a.webp'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function highlightItems(): array
    {
        return [
            ['title_variant' => 'author_title_subtitle', 'title' => 'La Riconoscenza della Repubblica Italiana a Napoleone, 1801-1802', 'author' => 'Giuseppe Bossi', 'subtitle_1' => 'Accademia di Belle arti di Brera', 'description' => 'Believed to have been lost during the Second World War, the painting was rediscovered in 1995 in the Brera Academy\'s storage rooms. It was later restored, returning it to its original splendour. The piece is central to the history of Neoclassical.', 'digital_object_slug' => 'OA_4t030-00055', 'sort_order' => 0, 'is_published' => true, 'cover_media_path' => 'media/320cb76b-cfa6-467a-851d-41c77e32264a.jpg'],
            ['title_variant' => 'author_title_subtitle', 'title' => 'Napoli. Museo Nazionale. Venere Callipige, 1870-1885', 'author' => 'Giorgio Sommer', 'subtitle_1' => 'Accademia di Belle arti di Brera', 'description' => 'This plate belongs to a volume of Il Bel Paese, a remarkable collection of 24 photographic albums forming a comprehensive iconographic survey of Italian landscapes, architecture and works of art.', 'digital_object_slug' => 'MIDFICCD_MIDF_8557354352771AI652S303AI652BP21_SC33_0602026_03_03', 'sort_order' => 1, 'is_published' => true, 'cover_media_path' => 'media/9f91af72-3348-4193-a431-8f11e7faf44b.jpg'],
            ['title_variant' => 'title_subtitle1_subtitle2', 'title' => 'Formazione Laboratorio Laboratori Scala Ansaldo, 2022-2023', 'subtitle_1' => 'photograph by Lonardo Giorgia', 'subtitle_2' => 'Accademia Teatro alla Scala', 'description' => 'Photo of the theatrical scenography department at Ansaldo laboratories.', 'digital_object_slug' => '2022-2023-6169', 'sort_order' => 2, 'is_published' => true, 'cover_media_path' => 'media/99a1ec7e-bf0b-485a-99c5-d0750b97b1c3.jpg'],
            ['title_variant' => 'title_subtitle1_subtitle2', 'title' => 'Andrea Appiani, after Leonardo da Vinci', 'subtitle_1' => 'Study of proportions from Leonardo\'s Vitruvian Man, 1770-1775', 'subtitle_2' => 'Accademia di Belle arti di Brera', 'description' => 'During his years of training at the school of Antonio De Giorgi at the Ambrosiana (1770), Andrea Appiani approached the study of Leonardo and other old masters.', 'digital_object_slug' => 'D_4t060-01883', 'sort_order' => 3, 'is_published' => true, 'cover_media_path' => 'media/366a7248-8de8-4f30-a49a-a01fdeef738a.jpg'],
            ['title_variant' => 'author_title_subtitle', 'title' => 'Composizione astratta colorata, 1950-1951', 'author' => 'Bice Lazzari', 'subtitle_1' => 'Accademia di Belle arti di Brera', 'description' => 'A work encompassing the artistic exploration of Bice Lazzari, a pioneer of twentieth-century Italian abstract art.', 'digital_object_slug' => 'D_4t060-04786', 'sort_order' => 4, 'is_published' => true, 'cover_iiif_identifier' => '97a0d1cf-f1fd-4f04-b10f-6269041aa23g.tif'],
            ['title_variant' => 'author_title_subtitle', 'title' => 'Carlo De Veroli with the plaster cast of Pope Rezzonico\'s bust after Antonio Canova', 'author' => 'Anonymous', 'subtitle_1' => 'Accademia di Belle Arti di Carrara', 'description' => 'The photograph documents an academic sculpture study, most likely produced within a classroom or studio of the Academy. The young artist portrayed beside the work is Carlo De Veroli (1890–1938), a student of the institution and nephew of the sculptor Arturo Dazzi, an important figure in Italian sculpture between the late nineteenth and early twentieth centuries. At the center of the image stands a plaster bust of a well-known figure, modeled with a strong expressive characterization. The marked facial features, deeply carved eyes, and solemn pose recall the tradition of academic portrait sculpture, in which the study of physiognomy and the rendering of the sitter’s character formed a fundamental component of artistic training. De Veroli is depicted beside the sculpture, establishing an almost dialogic relationship with the work; his hand resting on the base suggests both pride and a sense of engagement with the creative process. The photograph, therefore, also carries documentary value. Beyond recording a student exercise and the level of skill achieved by the young sculptor, it conveys the atmosphere of artistic practice within Italian academies in the early twentieth century, where plaster modeling played a central role in the teaching of sculptural techniques and in the study of portraiture. As such, the image preserves the memory of a formative moment in the artist’s education and reflects the continuity between academic tradition and the personal aspirations of a young sculptor.', 'digital_object_slug' => 'MIDFICCD_MIDF_7088549891671AI675S309', 'sort_order' => 5, 'is_published' => true, 'cover_iiif_identifier' => 'e43e09d3-e28b-4502-a5ad-671b856985c9.jpg'],
            ['title_variant' => 'author_title_subtitle', 'title' => '', 'author' => 'Album di Belle arti italiane, 1861 ', 'subtitle_1' => 'Biblioteca dell\'Accademia di Belle Arti di Carrara', 'description' => '', 'digital_object_slug' => 'LO10145460', 'sort_order' => 6, 'is_published' => false],
        ];
    }
}

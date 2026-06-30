<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Support\EditorialHtmlSanitizer;
use Illuminate\Console\Command;

class AuditPersonShortbioSanitization extends Command
{
    protected $signature = 'people:audit-shortbio-sanitization {--locale=en : Locale to inspect}';

    protected $description = 'Dry-run: compare stored shortbio vs sanitized output (no writes, no PII in output).';

    public function handle(EditorialHtmlSanitizer $sanitizer): int
    {
        $locale = (string) $this->option('locale');
        $changed = 0;
        $risky = 0;

        Person::query()
            ->where('status', 'published')
            ->orderBy('id')
            ->chunkById(100, function ($people) use ($sanitizer, $locale, &$changed, &$risky) {
                foreach ($people as $person) {
                    $raw = $person->getTranslation('shortbio', $locale);
                    if (! is_string($raw) || trim($raw) === '') {
                        continue;
                    }

                    $clean = $sanitizer->sanitize($raw) ?? '';
                    if ($clean !== $raw) {
                        $changed++;
                        $this->line(sprintf('id=%d slug=%s locale=%s changed=1', $person->id, $person->getTranslation('slug', $locale), $locale));
                    }

                    $lower = strtolower($raw);
                    if (str_contains($lower, '<script') || str_contains($lower, 'javascript:') || str_contains($lower, '<iframe')) {
                        $risky++;
                    }
                }
            });

        $this->info("Records needing sanitization delta: {$changed}");
        $this->info("Records with risky patterns in source HTML: {$risky}");

        return self::SUCCESS;
    }
}

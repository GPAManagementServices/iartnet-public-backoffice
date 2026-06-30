<?php

namespace App\Console\Commands;

use App\Models\PressPage;
use Illuminate\Console\Command;

class EnsurePressPageCommand extends Command
{
    protected $signature = 'press:ensure-page';

    protected $description = 'Ensure the Press singleton record exists (draft, no demo content).';

    public function handle(): int
    {
        $page = PressPage::resolveSingleton();

        $this->info("Press singleton ready (id={$page->id}, status={$page->status}).");

        return self::SUCCESS;
    }
}

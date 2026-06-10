<?php

namespace App\Console\Commands;

use App\Services\Files\TempFileService;
use Illuminate\Console\Command;

/*
|--------------------------------------------------------------------------
| PurgeAbandonedUploads
|--------------------------------------------------------------------------
| Removes temp upload directories from abandoned wizard sessions (started but
| never submitted). Successful submissions already purge their own files in
| WizardController::submit(); this reclaims the rest.
|
| Scheduled hourly (see routes/console.php / bootstrap scheduling). Can also
| be run manually:
|   php artisan uploads:purge
|   php artisan uploads:purge --minutes=240
*/

class PurgeAbandonedUploads extends Command
{
    protected $signature = 'uploads:purge {--minutes= : Max age in minutes before a temp dir is removed}';

    protected $description = 'Remove temporary upload directories from abandoned onboarding sessions';

    public function handle(TempFileService $files): int
    {
        $minutes = (int) ($this->option('minutes')
            ?: config('documents.temp_ttl_minutes', 180));

        $removed = $files->sweepOlderThan($minutes);

        $this->info("Purged {$removed} abandoned upload director(ies) older than {$minutes} minutes.");

        return self::SUCCESS;
    }
}

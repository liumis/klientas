<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SharePointSyncSecretCommand extends Command
{
    protected $signature = 'sharepoint:sync-secret';

    protected $description = 'Copy SHAREPOINT_CLIENT_SECRET env var into the settings table';

    public function handle(): int
    {
        if (! Schema::hasTable('settings')) {
            $this->error('Table `settings` does not exist.');

            return self::FAILURE;
        }

        $secret = config('services.sharepoint.client_secret');

        if (! filled($secret)) {
            $this->warn('SHAREPOINT_CLIENT_SECRET is not set in the environment.');

            return self::FAILURE;
        }

        Setting::query()->updateOrCreate(
            ['scope' => 'sharepoint', 'setting' => 'client_secret'],
            ['value' => $secret],
        );

        $this->info('SharePoint client_secret synced to settings table.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLog extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const INVALID = 2;

    protected $signature = 'log:clear';

    protected $description = 'Clear laravel.log file';

    public function handle(): int
    {
        exec('echo "" > '.storage_path('logs/laravel.log'));
        $this->info('Log file has been cleared.');

        return self::SUCCESS;
    }
}
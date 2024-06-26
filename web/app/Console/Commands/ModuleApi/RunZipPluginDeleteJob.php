<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\Update\ZipPluginDeleteJob;
use Illuminate\Console\Command;

class RunZipPluginDeleteJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module-api:zip-plugin-file-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs job that deletes obsolete zip plugin files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ZipPluginDeleteJob::dispatch();
        $this->info('ZipPluginDeleteJob dispatched successfully!');
    }
}

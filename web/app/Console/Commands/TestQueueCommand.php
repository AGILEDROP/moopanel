<?php

namespace App\Console\Commands;

use App\Jobs\TestQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom:test-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Custom command for testing queue';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Log::debug('Test queue command started.');

        $jobs = [];
        for($x = 0; $x <= 3; $x++) {
            $jobs[] = new TestQueueJob($x);
        }
        Bus::batch($jobs)->dispatch();

        Log::debug('Test queue jobs dispatched.');
    }
}

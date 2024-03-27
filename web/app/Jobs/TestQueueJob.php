<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $x;

    /**
     * Create a new job instance.
     */
    public function __construct(int $x)
    {
        $this->x = $x;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Test queue job is running ('.$this->x.')');
    }
}

<?php

namespace App\Jobs\SIS;

use App\Models\UniversityMember;
use App\Services\SisApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BaseSisJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public UniversityMember $universityMember;

    public SisApiService $sisApiService;

    public function __construct(UniversityMember $universityMember)
    {
        $this->universityMember = $universityMember;
        $this->sisApiService = new SisApiService();
    }
}

<?php

namespace App\Events;

use App\Models\Instance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstanceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Instance $instance;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }
}

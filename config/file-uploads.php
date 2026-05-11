<?php

use App\Models\Cluster;
use App\Models\Instance;

return [
    'disk' => 'public',
    'directory' => [
        Cluster::class => 'images\cluster',
        Instance::class => 'images\instance',
    ],
];

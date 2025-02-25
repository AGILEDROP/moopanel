<?php

return [
    'disk' => 'public',
    'directory' => [
        App\Models\Cluster::class => 'images\cluster',
        App\Models\Instance::class => 'images\instance',
    ],
];

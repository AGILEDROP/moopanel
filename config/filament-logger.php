<?php

use App\Filament\Admin\Clusters\Logs\LogsCluster;
use App\FilamentLogger\Loggers\AccessLogger;
use App\FilamentLogger\Loggers\ModelLogger;
use App\FilamentLogger\Loggers\NotificationLogger;
use App\FilamentLogger\Loggers\ResourceLogger;
use App\FilamentLogger\Resources\ActivityResource;

return [
    'datetime_format' => 'd/m/Y H:i:s',
    'date_format' => 'd/m/Y',

    'activity_resource' => ActivityResource::class,
    'scoped_to_tenant' => true,
    'navigation_sort' => 998,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Resource',
        'logger' => ResourceLogger::class,
        'color' => 'success',

        'exclude' => [
            // App\Filament\Resources\UserResource::class,
        ],
        'cluster' => LogsCluster::class,
        'navigation_group' => null,
    ],

    'access' => [
        'enabled' => true,
        'logger' => AccessLogger::class,
        'color' => 'danger',
        'log_name' => 'Access',
    ],

    'notifications' => [
        'enabled' => true,
        'logger' => NotificationLogger::class,
        'color' => null,
        'log_name' => 'Notification',
    ],

    'models' => [
        'enabled' => true,
        'log_name' => 'Model',
        'color' => 'warning',
        'logger' => ModelLogger::class,
        'register' => [
            // App\Models\User::class,
        ],
    ],

    'custom' => [
        // [
        //     'log_name' => 'Custom',
        //     'color' => 'primary',
        // ]
    ],
];

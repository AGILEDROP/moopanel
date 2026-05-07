<?php

use App\Filament\Admin\Clusters\Logs\Resources\ActivityLogResource;
use Z3d0X\FilamentLogger\Loggers\AccessLogger;
use Z3d0X\FilamentLogger\Loggers\ModelLogger;
use Z3d0X\FilamentLogger\Loggers\NotificationLogger;
use Z3d0X\FilamentLogger\Loggers\ResourceLogger;

return [
    'datetime_format' => 'd/m/Y H:i:s',
    'date_format' => 'd/m/Y',

    'activity_resource' => ActivityLogResource::class,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Resource',
        'logger' => ResourceLogger::class,
        'color' => 'success',
        'exclude' => [
            // App\Filament\Resources\UserResource::class,
        ],
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

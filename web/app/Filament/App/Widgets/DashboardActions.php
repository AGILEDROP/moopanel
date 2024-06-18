<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\Core;
use App\Filament\App\Resources\PluginResource\Pages\ManagePlugins;
use Filament\Widgets\Widget;

class DashboardActions extends Widget
{
    protected static string $view = 'filament.app.widgets.dashboard-actions';

    protected int|string|array $columnSpan = 2;

    public function getLinks(): array
    {
        return [
            [
                'label' => __('Assign administrators'),
                'iconComponent' => '<x-fas-user class="h-6 w-6 text-gray-700 dark:text-gray-500"></x-fas-user>',
                'url' => '#',
            ],
            [
                'label' => __('Create backup'),
                'iconComponent' => '<x-fas-database class="h-6 w-6 text-gray-700 dark:text-gray-500"></x-fas-database>',
                'url' => '#',
            ],
            [
                'label' => __('Compare with master'),
                'iconComponent' => '<x-fas-code-compare class="h-6 w-6 text-gray-700 dark:text-gray-500"></x-fas-code-compare>',
                'url' => '#',
            ],
            [
                'label' => __('Update core'),
                'iconComponent' => '<x-fas-cube class="h-6 w-6 text-gray-700 dark:text-gray-500"></x-fas-cube>',
                'url' => Core::getUrl(),
            ],
            [
                'label' => __('Update plugins'),
                'iconComponent' => '<x-fas-plug class="h-6 w-6 text-gray-700 dark:text-gray-500"></x-fas-plug>',
                'url' => ManagePlugins::getUrl().'?tableFilters[available_updates][value]=1', // Only show plugins with updates!
            ],
            [
                'label' => __('Terminal'),
                'labelTextColor' => 'text-primary-600 dark:text-primary-400',
                'iconComponent' => '<x-fas-terminal class="h-6 w-6 text-primary-600 dark:text-primary-400"></x-fas-terminal>',
                'url' => '#',
            ],
        ];
    }
}
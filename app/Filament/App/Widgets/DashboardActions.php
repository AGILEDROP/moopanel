<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Resources\PluginResource\Pages\ManagePlugins;
use Filament\Widgets\Widget;

class DashboardActions extends Widget
{
    protected string $view = 'filament.app.widgets.dashboard-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 2,
    ];

    public function getLinks(): array
    {
        return [
            /* [
                'label' => __('Assign administrators'),
                'icon' => 'fas-user',
                'url' => '#',
            ], */
            [
                'label' => __('Create backup'),
                'icon' => 'fas-database',
                'url' => route('filament.app.pages.courses', ['tenant' => filament()->getTenant()]),
            ],
            [
                'label' => __('Compare configurations'),
                'icon' => 'fas-code-compare',
                'url' => route('filament.admin.pages.delta-reports').'?first_instance_id='.filament()->getTenant()->id,
            ],
            /* [
                'label' => __('Update core'),
                'icon' => 'fas-cube',
                'url' => Core::getUrl(),
            ], */
            [
                'label' => __('Update plugins'),
                'icon' => 'fas-plug',
                'url' => ManagePlugins::getUrl().'?tableFilters[available_updates][value]=1', // Only show plugins with updates!
            ],
            /* [
                'label' => __('Terminal'),
                'labelTextColor' => 'text-primary-600 dark:text-primary-400',
                'icon' => 'fas-terminal',
                'url' => '#',
            ], */
        ];
    }
}

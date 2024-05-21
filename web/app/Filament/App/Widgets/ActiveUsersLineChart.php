<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\ChartWidget;

class ActiveUsersLineChart extends ChartWidget
{
    protected static ?string $heading = 'Number of active users';

    protected int|string|array $columnSpan = 3;

    protected static ?string $maxHeight = '400px';

    public ?string $filter = 'week';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => __('Today'),
            'week' => __('Weekly'),
            'month' => __('Monthly'),
            'year' => __('Yearly'),
        ];
    }

    private function getStartDate(): int
    {
        return match ($this->filter) {
            'today' => now()->startOfDay()->unix(),
            'week' => now()->subWeek()->unix(),
            'month' => now()->subMonth()->unix(),
            'year' => now()->subYear()->unix(),
        };
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // todo: Implement when users count endpoint will be created!
        // Ideally the endpoint should take in timestamp (startDate) and return user count from posted startDate to now with labels.

        return [
            'datasets' => [
                [
                    'label' => 'Active users',
                    'data' => [],
                    'fill' => true,
                ],
            ],
            'labels' => [],

        ];
    }
}

<?php

namespace App\Filament\App\Widgets;

use App\Models\ActiveMoodleUsersLog;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class ActiveMoodleUsersLineChart extends ChartWidget
{
    protected static ?string $heading = 'Number of active users';

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 6,
    ];

    protected static ?string $maxHeight = '350px';

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
            // 'today' => __('Today'),
            'week' => __('Weekly'),
            'month' => __('Monthly'),
            'year' => __('Yearly'),
        ];
    }

    private function getStartDate(): Carbon
    {
        return match ($this->filter) {
            // 'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
        };
    }

    private function getInterval(): string
    {
        return match ($this->filter) {
            // 'today' => 'hour',
            'week', 'month' => 'day',
            'year' => 'month',
        };
    }

    protected function getData(): array
    {
        // TODO: When issue bellow will be fixed implement also graph for today!
        // (PgsqlAdapter can be also overwritten if we need this display now).
        // Issue with pgsql adapter -> https://github.com/Flowframe/laravel-trend/issues/62
        $data = Trend::model(ActiveMoodleUsersLog::class)
            ->dateColumn('end_date')
            ->between(
                start: $this->getStartDate(),
                end: now(),
            )
            ->interval($this->getInterval())
            ->sum('active_num');

        return [
            'datasets' => [
                [
                    'label' => 'Active users',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}

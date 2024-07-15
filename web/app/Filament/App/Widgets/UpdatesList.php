<?php

namespace App\Filament\App\Widgets;

use App\Models\Instance;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class UpdatesList extends Widget
{
    protected static string $view = 'filament.app.widgets.updates-list';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 4,
    ];

    public string $type = 'core';

    public int $maxItems = 5;

    public function getList(): array
    {
        $itemsCount = 0;
        $listItems = [];
        $instance = Instance::find(filament()->getTenant()->id);

        // Get available updates.
        $availableUpdates = $this->getAvailableUpdates($instance);
        foreach ($availableUpdates as $update) {
            if ($itemsCount < $this->maxItems) {
                $name = ($this->type == 'core') ? $update->release : $update->plugin->display_name;
                $listItems[] = [
                    'name' => $name.' - '.__('available'),
                    'maturity' => $update->maturity,
                    'date' => __('Release date').': '.$update->version_date->format('d M Y'),
                    'item_type' => 'available',
                ];

                $itemsCount++;
            } else {
                break;
            }
        }

        // Get past updates (update log records), if items list is not full yet.
        $availableUpdatesCount = $itemsCount;
        if ($itemsCount < $this->maxItems) {
            $updatesLog = $this->getUpdateLog($instance, $itemsCount);
            foreach ($updatesLog as $update) {
                if ($itemsCount < $this->maxItems) {
                    $listItems[] = [
                        'name' => ($this->type == 'core') ?
                            (($availableUpdatesCount == $itemsCount) ? $instance->version.' - current' : $update->version) :
                            $update->plugin->display_name,
                        'date' => $update->timemodified->format('d M Y'),
                        'item_type' => ($this->type == 'core') ?
                            (($availableUpdatesCount == $itemsCount) ? 'current' : 'past') : (($update->plugin->display_name == $update->version) ? 'current' : 'past'),
                    ];

                    $itemsCount++;
                } else {
                    break;
                }
            }
        }

        return $listItems;
    }

    private function getAvailableUpdates(Instance $instance): Collection
    {
        return match ($this->type) {
            'core' => $instance->availableCoreUpdates()->orderBy('version', 'desc')->take($this->maxItems)->get(),
            'plugins' => $instance->availablePluginUpdates()->orderBy('version', 'desc')->take($this->maxItems)->get(),
        };
    }

    private function getUpdateLog(Instance $instance, int $itemsCount): Collection
    {
        return match ($this->type) {
            'core' => $instance->coreUpdateLog()
                ->whereIn('info', [
                    'Core installed',
                    'Core upgraded',
                ])
                ->latest('timemodified')
                ->take($itemsCount - $this->maxItems)
                ->get(),
            'plugins' => $instance->pluginUpdateLog()
                ->whereIn('info', [
                    'Plugin installed',
                    'Plugin upgraded',
                ])
                ->latest('timemodified')
                ->take($itemsCount - $this->maxItems)
                ->get(),
        };
    }
}

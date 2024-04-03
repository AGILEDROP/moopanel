<?php

namespace App\Filament\Resources\InstanceResource\Pages;

use App\Filament\Resources\InstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewInstance extends ViewRecord
{
    protected static string $resource = InstanceResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InstanceResource\Widgets\OnlineUsersWidget::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return $this->record->site_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}

<?php

namespace App\Filament\App\Pages;

use App\Jobs\ModuleApi\Sync;
use App\Models\Course;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Courses extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.pages.courses';

    public function getTitle(): string|Htmlable
    {
        return __('Courses');
    }

    protected function getTableHeaderActions(): array
    {
        return [
            TableAction::make('sync')
                ->label(__('Sync'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to sync courses from the Moodle instance?'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->sync()),
            /* ->after(function ($livewire) use ($refreshComponents) {
                if (! empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            }) */
        ];
    }

    public function mount(): void
    {
        //
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = self::getTitle();

        return $breadcrumbs;
    }

    private function sync(): void
    {
        $instance = filament()->getTenant();

        Notification::make()
            ->success()
            ->title(__('Course sync in progress'))
            ->body(__('This may take a few seconds. Check your notifications for updates.'))
            ->icon('heroicon-o-arrow-path')
            ->seconds(5)
            ->send();

        Sync::dispatch($instance, CourseSyncType::TYPE, 'Course sync failed.');

        //dd('Syncing courses. call course sync job');
        // Sync courses from Moodle
        // $this->dispatchBrowserEvent('notify', __('Courses synced successfully'));
    }

    /**
     * Query the table records.
     */
    protected function getTableQuery(): Builder
    {
        return Course::where('instance_id', filament()->getTenant()->id);
    }

    /**
     * Table query for export.
     */
    public function getTableQueryForExport(): Builder
    {
        throw new \Exception('Not implemented');

        return Course::query();
    }

    /**
     * Set table columns.
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('Course name'))
                ->weight(FontWeight::SemiBold)
                ->description(fn (Model $record) => $record->category->name)
                ->searchable()
                ->sortable(),
            TextColumn::make('category.name')
                ->label(__('Category'))
                ->searchable()
                ->sortable(),
            TextColumn::make('updated_at')
                ->label(__('Last updated'))
                ->sortable(),
        ];
    }

    /**
     * Set options for the table records per page select.
     *
     * @return int
     */
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    /**
     * Get the default number of records per page for the table.
     */
    protected function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 10;
    }
}

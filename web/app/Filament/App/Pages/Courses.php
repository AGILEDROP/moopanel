<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\InteractsWithCoursesTable;
use App\Jobs\ModuleApi\Sync;
use App\Models\Category;
use App\Models\Course;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Courses extends Page implements HasTable
{
    use InteractsWithCoursesTable;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

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
     * Set table filters.
     */
    protected function getTableFilters(): array
    {
        return [
            Filter::make('category')
                ->form([
                    SelectTree::make('categories')
                        ->relationship('category', 'name', 'parent_id')
                        ->independent(false)
                        ->enableBranchNode(),
                ])
                ->query(function (Builder $query, array $data) {
                    $query = $query->where('instance_id', filament()->getTenant()->id)
                        ->with('category')
                        ->when($data['categories'], function ($query, $categories) {

                            if (is_int($categories)) {
                                $categories = [$categories];
                            }

                            return $query->whereIn('category_id', $categories);
                        });

                    return $query;
                })
                ->indicateUsing(function (array $data): ?string {
                    if (! isset($data['categories']) || empty($data['categories'])) {
                        return null;
                    }

                    if (is_int($data['categories'])) {
                        $data['categories'] = [$data['categories']];
                    }

                    return __('Categories').': '.implode(', ', Category::whereIn('id', $data['categories'])->get()->pluck('name')->toArray());
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            TableAction::make('backup')
                ->label(__('Backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to backup selected course?'))
                ->icon('heroicon-o-cloud-arrow-up')
                ->action(fn () => dd('Single manual Backup TODO implement')),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            TableAction::make('backup')
                ->label(__('Bulk Backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to backup selected courses from the Moodle instance?'))
                ->icon('heroicon-o-cloud-arrow-up')
                ->action(fn () => dd('Bulk Backup TODO implement')),
        ];
    }

    /**
     * Set table filters form width.
     */
    protected function getTableFiltersFormWidth(): MaxWidth
    {
        return MaxWidth::FourExtraLarge;
    }

    /**
     * Set table filters columns.
     */
    protected function getTableFilterColumns(): int
    {
        return 2;
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

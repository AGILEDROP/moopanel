<?php

namespace App\Filament\App\Pages;

use App\Enums\BackupStorageType;
use App\Filament\Concerns\InteractsWithCoursesTable;
use App\Jobs\Backup\BackupRequestJob;
use App\Jobs\ModuleApi\Sync;
use App\Models\BackupStorage;
use App\Models\Category;
use App\Models\Course;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
            Action::make('sync')
                ->label(__('Sync'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to sync courses from the Moodle instance?'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->sync()),
        ];
    }

    /**
     * Get the polling interval for the table to refresh synced courses and categories
     */
    protected function getTablePollingInterval(): string
    {
        return '7s';
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

        Sync::dispatchSync($instance, CourseSyncType::TYPE, 'Course sync failed.');
    }

    /**
     * Backup selected courses in the table
     */
    public function backupCourses(Collection $courses): void
    {
        $moodleCourseIds = $courses->pluck('moodle_course_id')->toArray();

        $additionalTempCourseData = [];
        foreach ($courses as $course) {
            $additionalTempCourseData[] = [
                'moodle_course_id' => $course->moodle_course_id,
                'course_id' => $course->id,
            ];
        }

        $instanceId = filament()->getTenant()->id;
        $instanceBackupStorage = BackupStorage::where('instance_id', $instanceId)
            ->where('active', true)
            ->first();

        if (! $instanceBackupStorage) {
            Notification::make()
                ->danger()
                ->title(__('No active backup storage'))
                ->body(__('Please configure active backup storage in the settings.'))
                ->send();

            return;
        }

        // TODO: remove in version 2.0, when other storage types are supported
        if ($instanceBackupStorage->storage_key !== BackupStorageType::Local->value) {
            Notification::make()
                ->danger()
                ->title(__('Unsupported local storage'))
                ->body(__('Please switch to local backup storage to perform backup. Other storage types are not supported yet.'))
                ->send();

            return;
        }

        // Backup storage settings
        $storage = $instanceBackupStorage->storage_key;
        // TODO: add credenitals in version 2.0, when other storage types are supported
        /* $credentials = [
            'url' => $instanceBackupStorage->url,
            'api-key' => $instanceBackupStorage->key,
        ]; */
        $credentials = [];

        $payload = [
            'instance_id' => $instanceId,
            'storage' => $storage,
            'credentials' => $credentials,
            'courses' => $moodleCourseIds,
            'temp' => $additionalTempCourseData,
            'mode' => 'manual',
        ];

        BackupRequestJob::dispatch(auth()->user(), $payload, true);

        Notification::make()
            ->success()
            ->title(__('Backup request sent'))
            ->body(__('Backup request for selected courses has been sent.'))
            ->icon('heroicon-o-circle-stack')
            ->send();
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
            TextColumn::make('is_scheduled')
                ->numeric()
                ->state(fn (Course $course): string => $course->is_scheduled ? __('Enabled') : __('Disabled'))
                ->badge()
                ->icon(fn (Course $course): string => $course->is_scheduled ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color(fn (Course $course): string => $course->is_scheduled ? 'success' : 'danger')
                ->tooltip(fn (Course $course): string => $course->is_scheduled ? __('Click to disable auto-backup') : __('Click to enable auto-backup'))
                ->action(
                    Action::make('is_scheduled')
                        ->requiresConfirmation()
                        ->modalHeading(function (Course $course): string {
                            return $course->is_scheduled ? __('Disable auto-backup') : __('Enable auto-backup');
                        })
                        ->modalDescription(function (Course $course): string {
                            return $course->is_scheduled ? __('Do you want to disable auto-backup for course :course?', ['course' => $course->name]) : __('Do you want to enable auto-backup for course :course?', ['course' => $course->name]);
                        })
                        ->color(function (Course $course): string {
                            return $course->is_scheduled ? 'danger' : 'success';
                        })
                        ->modalIcon('heroicon-o-circle-stack')
                        ->action(function (Course $course): void {
                            $course->update(['is_scheduled' => ! $course->is_scheduled]);

                            $state = $course->is_scheduled ? __('enabled') : __('disabled');
                            Notification::make()
                                ->success()
                                ->title(__('Auto-backup state updated'))
                                ->body(__('Auto-backup has been :state for course :course.', ['state' => $state, 'course' => $course->name]))
                                ->icon('heroicon-o-arrow-path')
                                ->send();
                        }),
                ),
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
                        ->relationship(
                            relationship: 'category',
                            titleAttribute: 'name',
                            parentAttribute: 'parent_id',
                            modifyQueryUsing: fn ($query) => $query->where('instance_id', filament()->getTenant()->id)->orderBy('name')
                        )
                        ->independent(false)
                        ->searchable()
                        ->withCount()
                        ->enableBranchNode(),
                ])
                ->query(function (Builder $query, array $data) {
                    $query = $query->where('instance_id', filament()->getTenant()->id)
                        ->with('category')
                        ->when($data['categories'], function ($query, $categories) {

                            if (is_int($categories)) {
                                $categories = [$categories];
                            }

                            // Retrieve all category IDs of the subtree
                            $allCategoryIds = [];
                            foreach ($categories as $categoryId) {
                                $allCategoryIds[] = $categoryId;
                                $descendantIds = $this->getAllDescendantCategoryIds((int) $categoryId);
                                $allCategoryIds = array_merge($allCategoryIds, $descendantIds);
                            }

                            return $query->whereIn('category_id', $allCategoryIds);
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
            Filter::make('is_scheduled')
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->where('is_scheduled', true)),
        ];
    }

    /**
     * Get all descendant category ids for the given category id
     */
    private function getAllDescendantCategoryIds(int $categoryId): array
    {
        $descendantIds = [];
        $childCategories = Category::where('parent_id', $categoryId)->get();

        foreach ($childCategories as $childCategory) {
            $descendantIds[] = $childCategory->id;
            $descendantIds = array_merge($descendantIds, $this->getAllDescendantCategoryIds($childCategory->id));
        }

        return $descendantIds;
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('backup')
                ->label(__('Backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to backup selected course?'))
                ->modalIcon('heroicon-o-circle-stack')
                ->icon('heroicon-o-circle-stack')
                ->action(fn (Course $course) => $this->backupCourses(collect([$course]))),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('bulk_backup')
                ->label(__('Bulk Backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to backup selected courses from the Moodle instance?'))
                ->modalIcon('heroicon-o-circle-stack')
                ->icon('heroicon-o-circle-stack')
                ->action(function (Collection $selectedRecords) {
                    $this->backupCourses($selectedRecords);
                }),
            BulkAction::make('bulk_schedule_backup')
                ->label(__('Enable auto-backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want enable auto-backup for selected courses?'))
                ->modalIcon('heroicon-o-arrow-path')
                ->icon('heroicon-o-arrow-path')
                ->action(function (Collection $selectedRecords) {
                    $courseIds = $selectedRecords->pluck('id')->toArray();

                    Course::whereIn('id', $courseIds)->update(['is_scheduled' => true]);

                    Notification::make()
                        ->success()
                        ->title(__('Courses scheduled for auto-backup'))
                        ->body(__('Selected courses have been scheduled for auto-backup.'))
                        ->icon('heroicon-o-arrow-path')
                        ->send();
                }),
            BulkAction::make('bulk_cancel_schedule_backup')
                ->label(__('Disable auto-backup'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want disable auto-backup for selected courses?'))
                ->modalIcon('heroicon-o-x-circle')
                ->icon('heroicon-o-x-circle')
                ->action(function (Collection $selectedRecords) {
                    $courseIds = $selectedRecords->pluck('id')->toArray();

                    Course::whereIn('id', $courseIds)->update(['is_scheduled' => false]);

                    Notification::make()
                        ->success()
                        ->title(__('Auto-backup canceled'))
                        ->body(__('Auto-backup has been canceled for selected courses.'))
                        ->icon('heroicon-o-x-circle')
                        ->send();
                }),
        ];
    }

    /**
     * Set table filters form width.
     */
    protected function getTableFiltersFormWidth(): MaxWidth
    {
        return MaxWidth::Small;
    }

    /**
     * Set table filters columns.
     */
    protected function getTableFilterColumns(): int
    {
        return 1;
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

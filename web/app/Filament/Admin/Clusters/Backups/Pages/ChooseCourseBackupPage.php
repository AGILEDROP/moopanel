<?php

namespace App\Filament\Admin\Clusters\Backups\Pages;

use App\Enums\BackupType;
use App\Filament\Concerns\InteractsWithCoursesTable;
use App\Jobs\Backup\BackupRequestJob;
use App\Models\Category;
use App\Models\Course;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ChooseCourseBackupPage extends BaseBackupWizardPage implements HasTable
{
    use InteractsWithCoursesTable;

    protected static string $view = 'filament.admin.pages.choose-course-page';

    protected static ?string $title = 'Create backups';

    protected static ?string $slug = 'create-backups';

    public int $currentStep = 4;

    public bool $hasHeaderAction = false;

    public function getTypes(): array
    {
        $types = [];
        foreach (BackupType::cases() as $case) {
            $types[] = [
                'class' => 'xl:w-[340px]',
                'type' => $case->value,
                'text' => $case->getText(),
                'icon' => $case->getIconComponent('h-32 w-32 mx-auto text-gray-500 dark:text-gray-300 mb-8'),
                'count' => false,
            ];
        }

        return $types;
    }

    public function selectType(?string $type): void
    {
        if ($type !== $this->type) {
            $this->type = $type;
        } else {
            $this->type = null;
        }
    }

    public function isSelected(?string $type): bool
    {
        return $type === $this->type;
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseBackupTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
        ]));
    }

    public function goToNextStep(): void
    {
        dd('Final step reached');
    }

    /**
     * Backup courses, selected in the table or single course triggered with row action
     */
    public function backupCourses(Collection $courses): void
    {
        // Request backup only on instances that have selected courses
        $instanceIds = $courses->pluck('instance_id')->unique();

        foreach ($instanceIds as $instanceId) {

            $additionalTempCourseData = [];
            foreach ($courses->where('instance_id', $instanceId) as $course) {
                $additionalTempCourseData[] = [
                    'moodle_course_id' => $course->moodle_course_id,
                    'course_id' => $course->id,
                ];
            }

            $payload = [
                'instance_id' => $instanceId,

                // TODO: add dnynamic storage info - maybe from settings
                'storage' => 'local',
                'credentials' => [
                    'url' => 'https://test-link-for-storage.com/folder',
                    'api-key' => 'abcd1234',
                ],

                // Request backup only for courses that belong to current instance
                'courses' => $courses->where('instance_id', $instanceId)->pluck('moodle_course_id')->toArray(),
                'temp' => $additionalTempCourseData,
            ];

            BackupRequestJob::dispatch(auth()->user(), $payload, true);
        }

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
        return Course::whereIn('instance_id', $this->instanceIds)
            ->with('category');
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
            TextColumn::make('instance.name')
                ->label(__('Instance'))
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
                    $query = $query->whereIn('instance_id', $this->instanceIds)
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

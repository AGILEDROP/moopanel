<?php

namespace App\Filament\Admin\Clusters\Backups\Pages;

use App\Enums\BackupType;
use App\Filament\Concerns\InteractsWithCoursesTable;
use App\Models\Category;
use App\Models\Course;
use CodeWithDennis\FilamentSelectTree\SelectTree;
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
        // todo: implement next page based on selection.
        dd('TODO backup selected courses');
    }

    public function backupCourses(Collection $courses): void
    {
        //
        $moodleCourseIds = $courses->pluck('moodle_course_id')->toArray();
        dd('TODO: running backup for courses.', $courses, $moodleCourseIds);
    }

    public function backupAll(): void
    {
        $allCourseIds = $this->getTableQuery()->get()->pluck('id')->toArray();

        //TODO: be careful to send moodle_course_id-s
        dd('TODO: running backup for all courses.', $allCourseIds);
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

<?php

namespace App\Filament\Concerns;

use Closure;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait InteractsWithCoursesTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $recordSelectableCallback = $this->getCoursesTableRecordSelectableUsing();

        return $table
            ->query($this->getCoursesTableQuery())
            ->columns($this->getCoursesTableColumns())
            ->recordActions($this->getCoursesTableRecordActions())
            ->when($recordSelectableCallback, fn (Table $table): Table => $table->checkIfRecordIsSelectableUsing($recordSelectableCallback))
            ->filters($this->getCoursesTableFilters(), FiltersLayout::AboveContent)
            ->when($this->getCoursesTableFiltersFormWidth(), fn (Table $table, string $width): Table => $table->filtersFormWidth($width))
            ->when($this->getCoursesTableFilterColumns(), fn (Table $table, int | array $columns): Table => $table->filtersFormColumns($columns))
            ->when($this->getCoursesTableBulkActions(), fn (Table $table, array $actions): Table => $table->groupedBulkActions($actions))
            ->when($this->getCoursesTableHeaderActions(), fn (Table $table, array $actions): Table => $table->headerActions($actions))
            ->when($this->getCoursesTablePaginationPageOptions(), fn (Table $table, array $options): Table => $table->paginationPageOptions($options))
            ->when($this->getCoursesTablePollingInterval(), fn (Table $table, string $interval): Table => $table->poll($interval));
    }

    protected function getCoursesTableQuery(): Builder | Relation | null
    {
        return null;
    }

    protected function getCoursesTableColumns(): array
    {
        return [];
    }

    protected function getCoursesTableFilters(): array
    {
        return [];
    }

    protected function getCoursesTableRecordActions(): array
    {
        return [];
    }

    protected function getCoursesTableBulkActions(): array
    {
        return [];
    }

    protected function getCoursesTableHeaderActions(): array
    {
        return [];
    }

    protected function getCoursesTablePollingInterval(): ?string
    {
        return null;
    }

    protected function getCoursesTableFiltersFormWidth(): ?string
    {
        return null;
    }

    protected function getCoursesTableFilterColumns(): int | array | null
    {
        return null;
    }

    protected function getCoursesTablePaginationPageOptions(): ?array
    {
        return null;
    }

    protected function getCoursesTableDefaultRecordsPerPageOption(): int | string | null
    {
        return null;
    }

    protected function getCoursesTableRecordSelectableUsing(): ?Closure
    {
        return method_exists($this, 'isTableRecordSelectable')
            ? $this->isTableRecordSelectable()
            : null;
    }
}

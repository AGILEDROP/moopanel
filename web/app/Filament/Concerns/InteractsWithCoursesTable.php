<?php

namespace App\Filament\Concerns;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithCoursesTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->actions($this->getTableActions())
            ->actionsColumnLabel($this->getTableActionsColumnLabel())
            ->checkIfRecordIsSelectableUsing($this->isTableRecordSelectable())
            ->columns($this->getTableColumns())
            ->columnToggleFormColumns($this->getTableColumnToggleFormColumns())
            ->columnToggleFormMaxHeight($this->getTableColumnToggleFormMaxHeight())
            ->columnToggleFormWidth($this->getTableColumnToggleFormWidth())
            ->content($this->getTableContent())
            ->contentFooter($this->getTableContentFooter())
            ->contentGrid($this->getTableContentGrid())
            ->defaultSort($this->getDefaultTableSortColumn(), $this->getDefaultTableSortDirection())
            ->deferLoading($this->isTableLoadingDeferred())
            ->description($this->getTableDescription())
            ->deselectAllRecordsWhenFiltered($this->shouldDeselectAllRecordsWhenTableFiltered())
            ->emptyState($this->getTableEmptyState())
            ->emptyStateActions($this->getTableEmptyStateActions())
            ->emptyStateDescription($this->getTableEmptyStateDescription())
            ->emptyStateHeading($this->getTableEmptyStateHeading())
            ->emptyStateIcon($this->getTableEmptyStateIcon())
            ->filters($this->getTableFilters(), FiltersLayout::AboveContent)
            ->filtersFormMaxHeight($this->getTableFiltersFormMaxHeight())
            ->filtersFormWidth($this->getTableFiltersFormWidth())
            ->filtersFormColumns($this->getTableFilterColumns())
            ->groupedBulkActions($this->getTableBulkActions())
            ->header($this->getTableHeader())
            ->headerActions($this->getTableHeaderActions())
            ->modelLabel($this->getTableModelLabel())
            ->paginated($this->isTablePaginationEnabled())
            ->paginatedWhileReordering($this->isTablePaginationEnabledWhileReordering())
            ->paginationPageOptions($this->getTableRecordsPerPageSelectOptions())
            ->persistFiltersInSession($this->shouldPersistTableFiltersInSession())
            ->persistSearchInSession($this->shouldPersistTableSearchInSession())
            ->persistColumnSearchesInSession($this->shouldPersistTableColumnSearchInSession())
            ->persistSortInSession($this->shouldPersistTableSortInSession())
            ->pluralModelLabel($this->getTablePluralModelLabel())
            ->poll($this->getTablePollingInterval())
            ->recordAction($this->getTableRecordActionUsing())
            ->recordClasses($this->getTableRecordClassesUsing())
            ->recordTitle(fn (Model $record): ?string => $this->getTableRecordTitle($record))
            ->recordUrl($this->getTableRecordUrlUsing())
            ->reorderable($this->getTableReorderColumn())
            ->selectCurrentPageOnly($this->shouldSelectCurrentPageOnly())
            ->striped($this->isTableStriped());
    }
}

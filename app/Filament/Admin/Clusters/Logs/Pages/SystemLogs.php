<?php

namespace App\Filament\Admin\Clusters\Logs\Pages;

use AchyutN\FilamentLogViewer\Actions\CopyMarkdownAction;
use AchyutN\FilamentLogViewer\Enums\LogLevel;
use AchyutN\FilamentLogViewer\Filters\DateRangeFilter;
use AchyutN\FilamentLogViewer\Filters\FileFilter;
use AchyutN\FilamentLogViewer\Model\Log;
use AchyutN\FilamentLogViewer\Schema\ErrorLogSchema;
use AchyutN\FilamentLogViewer\Schema\JSONLogSchema;
use AchyutN\FilamentLogViewer\Schema\LogTableSchema;
use AchyutN\FilamentLogViewer\Schema\MailLogSchema;
use AchyutN\FilamentLogViewer\Traits\LogLevelTabFilter;
use App\Enums\Role;
use App\Filament\Admin\Clusters\Logs\LogsCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * @phpstan-import-type LogRow from Log
 * @phpstan-type LogCollection Collection<int|string, LogRow>
 * @phpstan-type FilterData array{date?: array{from?: string, until?: string}, file?: array{value: string}}
 */
class SystemLogs extends Page implements HasTable
{
    use InteractsWithTable;
    use LogLevelTabFilter;

    protected string $view = 'filament-log-viewer::log-table';

    protected static ?string $cluster = LogsCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'fas-server';

    protected static ?string $slug = 'system-logs';

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            LogsCluster::getUrl() => LogsCluster::getClusterBreadcrumb(),
        ];
    }

    public function getHeading(): string
    {
        return __('System Logs');
    }

    public function getSubheading(): ?string
    {
        return __('Laravel application logs');
    }

    public function getTitle(): string
    {
        return __('System Logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('System Logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(__('filament-log-viewer::log.table.model_label'))
            ->pluralModelLabel(__('filament-log-viewer::log.table.plural_model_label'))
            ->records(
                function (?array $filters, ?string $sortColumn, ?string $sortDirection, ?string $search, int $page, int $recordsPerPage): LengthAwarePaginator {
                    $records = Collection::wrap(Log::getRows());

                    $records = $this->applyTabFilter($records);
                    /** @var FilterData $filters */
                    $records = $this->applyDateFilter($records, $filters);
                    /** @var FilterData $filters */
                    $records = $this->applyFileFilter($records, $filters);
                    $records = $this->applySearchFilter($records, $search);

                    $records = filled($sortColumn)
                        ? $records->sortBy($sortColumn, SORT_DESC, $sortDirection === 'desc')
                        : $records->sortByDesc('date');

                    $paginatedRecords = $records->forPage($page, $recordsPerPage);

                    return new LengthAwarePaginator(
                        $paginatedRecords,
                        total: count($records),
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                }
            )
            ->columns(LogTableSchema::columns())
            ->recordActions([
                Action::make('view')
                    ->label(__('filament-log-viewer::log.table.actions.view.label'))
                    ->visible(fn (array $record): bool => $record['log_level'] !== LogLevel::MAIL)
                    ->hidden(fn (array $record): bool => ! $record['has_stack'])
                    ->icon(Heroicon::Eye)
                    ->color(Color::Gray)
                    ->schema(fn (Schema $schema): Schema => ErrorLogSchema::configure($schema))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalHeading(fn (array $record) => $record['message'])
                    ->modalDescription(fn (array $record) => $record['description'])
                    ->slideOver(),
                Action::make('view-json')
                    ->label(__('filament-log-viewer::log.table.actions.view.label'))
                    ->visible(fn (array $record): bool => $record['log_level'] !== LogLevel::MAIL)
                    ->hidden(fn (array $record): bool => $record['context'] === null)
                    ->icon(Heroicon::Eye)
                    ->color(Color::Gray)
                    ->schema(fn (Schema $schema): Schema => JSONLogSchema::configure($schema))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalHeading(fn (array $record) => $record['message'])
                    ->modalDescription(fn (array $record) => $record['description'])
                    ->slideOver(),
                Action::make('read')
                    ->label(__('filament-log-viewer::log.table.actions.read.label'))
                    ->visible(fn (array $record): bool => $record['log_level'] === LogLevel::MAIL)
                    ->icon(Heroicon::Envelope)
                    ->color(Color::hex('#9C27B0'))
                    ->schema(fn (Schema $schema): Schema => MailLogSchema::configure($schema))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalHeading(function (array $record): string {
                        $mail = $record['mail'];

                        if ($mail && isset($mail['subject']) && $mail['subject'] !== '') {
                            return __('filament-log-viewer::log.table.actions.read.subject') . ': ' . $mail['subject'];
                        }

                        return __('filament-log-viewer::log.table.actions.read.mail_log');
                    })
                    ->modalDescription(function (array $record): ?string {
                        $mail = $record['mail'];

                        if ($mail && isset($mail['sent_date']) && $mail['sent_date'] !== '') {
                            return __('filament-log-viewer::log.table.actions.read.sent_date') . ': ' . $mail['sent_date'];
                        }

                        return null;
                    })
                    ->slideOver(),
                CopyMarkdownAction::make(),
            ])
            ->filters([
                DateRangeFilter::make('date')
                    ->columnSpan(2),
                FileFilter::make()
                    ->columnSpan(1),
            ])
            ->filtersFormWidth(Width::Large)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->deferColumnManager(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('filament-log-viewer::log.table.actions.refresh.label'))
                ->icon(Heroicon::ArrowPath)
                ->outlined()
                ->action(function (): void {
                    $this->refresh();
                }),
            Action::make('clear')
                ->label(__('filament-log-viewer::log.table.actions.clear.label'))
                ->icon(Heroicon::Trash)
                ->color(Color::Red)
                ->visible(fn (): bool => config('filament-log-viewer.enable_delete', true))
                ->requiresConfirmation()
                ->action(function (): void {
                    Log::destroyAllLogs();

                    Notification::make()
                        ->title(__('filament-log-viewer::log.table.actions.clear.success'))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @param  LogCollection  $records
     * @return LogCollection
     */
    private function applyTabFilter(Collection $records): Collection
    {
        if ($this->tableIsUnscoped()) {
            return $records;
        }

        return $records->filter(fn (array $log): bool => $log['log_level']->value === $this->activeTab);
    }

    /**
     * @param  LogCollection  $records
     * @param  FilterData|null  $filters
     * @return LogCollection
     */
    private function applyDateFilter(Collection $records, ?array $filters): Collection
    {
        if (empty($filters['date'])) {
            return $records;
        }

        $from = $filters['date']['from'] ?? null;
        $until = $filters['date']['until'] ?? null;

        if (filled($from)) {
            $records = $records->filter(
                fn (array $log): bool => $log['date'] >= $from
            );
        }

        if (filled($until)) {
            return $records->filter(
                fn (array $log): bool => $log['date'] <= $until
            );
        }

        return $records;
    }

    /**
     * @param  LogCollection  $records
     * @param  FilterData|null  $filters
     * @return LogCollection
     */
    private function applyFileFilter(Collection $records, ?array $filters): Collection
    {
        if (! $filters || (array_key_exists('file', $filters) === false || blank($filters['file']['value']))) {
            return $records;
        }

        $file = mb_strtolower($filters['file']['value']);

        return $records->filter(fn (array $log): bool => mb_strtolower($log['file']) === $file);
    }

    /**
     * @param  LogCollection  $records
     * @return LogCollection
     */
    private function applySearchFilter(Collection $records, ?string $search): Collection
    {
        if (blank($search)) {
            return $records;
        }

        $needle = mb_strtolower($search);

        return $records->filter(fn (array $log): bool => str_contains(mb_strtolower($log['message']), $needle));
    }
}

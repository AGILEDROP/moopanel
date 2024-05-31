<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Helpers\SyncHelper;
use App\Models\Instance;
use App\Models\Sync;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SyncType;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaseSyncType implements SyncType
{
    protected ModuleApiService $moduleApiService;

    protected SyncHelper $syncHelper;

    protected Instance $instance;

    // todo: you can use enum instead of const variables!
    const TYPE = null;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
        $this->moduleApiService = new ModuleApiService();
        $this->syncHelper = new SyncHelper();
    }

    /**
     * Trigger sync.
     */
    public function run(bool $silent = false): bool
    {
        $success = false;
        DB::beginTransaction();
        try {
            $this->syncData();

            DB::commit();
            $success = true;
            if (! $silent) {
                $this->getSuccessNotification();
            }
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());

            $this->syncHelper->setInstanceStatusToDisconnected($this->instance);

            if (! $silent) {
                $this->getFailNotification();
            }
        }

        return $success;
    }

    /**
     * Execute sync logic specific to sync type.
     *
     * @throws Exception
     */
    protected function syncData(): void
    {
        // Overwrite in each sync type class!
        throw new Exception("Base class shouldn't be used for syncing data.");
    }

    /**
     * Trigger filament notification after successful sync.
     */
    protected function getSuccessNotification(): void
    {
        Notification::make()
            ->title(__('Data is synced.'))
            ->success()
            ->send();
    }

    /**
     * Trigger filament notification after unsuccessful sync.
     */
    protected function getFailNotification(): void
    {
        Notification::make()
            ->title(__('Sync failed!'))
            ->danger()
            ->send();
    }

    /**
     * Get last sync record.
     */
    public function getLatest(): ?Model
    {
        return Sync::where([
            ['instance_id', '=', $this->instance->id],
            ['type', '=', static::TYPE],
        ])->latest('synced_at')->first();
    }

    /**
     * Get text for displaying last sync time (Last sync: $diffForHumans/never).
     */
    public function getLatestTimeText(): string
    {
        $time = __('never');
        if (isset($this->instance->id)) {
            $lastSync = $this->getLatest();
            $time = ($lastSync) ? $lastSync->synced_at->diffForHumans() : $time;
        }

        return __('Last sync: ').$time;
    }

    /**
     * Return table action which trigger table records sync and refresh selected components.
     */
    public function getTableAction(string $name, array $refreshComponents): TableAction
    {
        return TableAction::make($name)
            ->label(__('Sync'))
            ->icon('heroicon-o-arrow-path')
            ->action(fn () => $this->run(true))
            ->after(function ($livewire) use ($refreshComponents) {
                if (! empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }

    /**
     * Return table action which trigger table records sync and refresh selected components.
     */
    public function getHeaderAction(string $name, array $refreshComponents): Action
    {
        return Action::make($name)
            ->label(__('Sync'))
            ->icon('heroicon-o-arrow-path')
            ->action(fn () => $this->run(true))
            ->after(function ($livewire) use ($refreshComponents) {
                if (! empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }
}

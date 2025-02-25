<?php

namespace App\UseCases\Syncs;

use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;

interface SyncType
{
    public function run(): bool;

    public function getLatest(): ?Model;

    public function getLatestTimeText(): string;

    public function getTableAction(string $name, array $refreshComponents): TableAction;

    public function getHeaderAction(string $name, array $refreshComponents): Action;
}

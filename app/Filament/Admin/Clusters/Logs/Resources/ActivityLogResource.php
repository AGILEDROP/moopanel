<?php

namespace App\Filament\Admin\Clusters\Logs\Resources;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Logs;
use App\Filament\Admin\Clusters\Logs\Resources\ActivityLogResource\Pages;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use Z3d0X\FilamentLogger\Resources\ActivityResource;

class ActivityLogResource extends ActivityResource
{
    protected static ?string $cluster = Logs::class;

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function getNavigationIcon(): string
    {
        return 'fas-clipboard-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    // Use if you want to show only users activity log!
    //    public static function table(Table $table): Table
    //    {
    //        $table = parent::table($table);
    //        return $table->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('causer_id'));
    //    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}

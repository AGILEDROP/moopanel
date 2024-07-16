<?php

namespace App\Filament\App\Pages;

use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UpdateRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.pages.update-requests';

    public ?int $updateRequestId = null;

    public ?UpdateRequest $updateRequest = null;

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        $title = __('Update Request items');

        if ($this->updateRequest && ! empty($this->updateRequest->name)) {
            $title = $this->updateRequest->name.__(' - update request items');
        }

        return $title;
    }

    public function mount(): void
    {
        // Render update request items
        if (! empty(request()->query('id', ''))) {
            $updateRequestId = (int) request()->query('id', '');
            $this->updateRequest = UpdateRequest::find($updateRequestId);

            // Check if the user is the owner of the update request
            if (Auth::user()->id === $this->updateRequest->user_id) {
                $this->updateRequestId = $updateRequestId;

                return;
            }
        }

        // Redirect to instance dashboard if no update request id is provided
        redirect()->route('filament.app.pages.app-dashboard', ['tenant' => filament()->getTenant()]);
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = self::getTitle();

        return $breadcrumbs;
    }

    protected function getTableQuery(): Builder
    {
        return UpdateRequestItem::where('update_request_id', $this->updateRequestId);
    }

    protected function getTableColumns(): array
    {
        $updateRequest = UpdateRequest::find($this->updateRequestId);

        // Column array manual generation because of dependency injection issues in hidden() method of filament
        $columns = [
            TextColumn::make('statusName')
                ->label(__('Status'))
                ->color(fn (Model $model) => is_null($model->status) ? 'warning' : ($model->status ? 'success' : 'danger'))
                ->badge(),
            TextColumn::make('component')
                ->weight(FontWeight::Bold),
            TextColumn::make('version'),
        ];

        if ($updateRequest && $updateRequest->type !== UpdateRequest::TYPE_PLUGIN_ZIP) {
            $columns[] = TextColumn::make('model_id')
                ->label(__('UpdateID'));
            $columns[] = TextColumn::make('release');
        }

        $columns[] = TextColumn::make('error')
            ->label(__('Message'));

        $columns[] = TextColumn::make('created_at')
            ->label(__('Requested at'));

        $columns[] = TextColumn::make('updated_at')
            ->label(__('Last change'));

        return $columns;
    }
}

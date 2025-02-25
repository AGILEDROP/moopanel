<?php

namespace App\Filament\App\Pages;

use App\Jobs\AzureApi\AzureAppDataSyncJob;
use App\Models\UniversityMember;
use App\Rules\AzureAppId;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\Rule;

class InstanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.app.pages.instance-settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 7;

    public function mount(): void
    {
        $this->form->fill(filament()->getTenant()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('azure_app_id')
                    ->label(__('Azure App ID'))
                    ->rules([
                        new AzureAppId(),
                        Rule::unique('instances', 'azure_app_id')->ignore(filament()->getTenant()->id),
                    ])
                    ->required(),
                Select::make('university_member_id')
                    ->label('University member')
                    ->options(UniversityMember::all()->pluck('name', 'id'))
                    ->rules([
                        'exists:university_members,id',
                        Rule::unique('instances', 'university_member_id')->ignore(filament()->getTenant()->id),
                    ])
                    ->searchable()
                    ->required(),
                Textarea::make('app_info')
                    ->label('Azure AD app info')
                    // Pretify JSON
                    ->formatStateUsing(fn (?string $state): string => $state ? json_encode(json_decode($state, true), JSON_PRETTY_PRINT) : 'Instance has no Azure AD app info.')
                    ->autosize()
                    ->readOnly(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            filament()->getTenant()->update($data);
        } catch (Halt $exception) {
            Notification::make()
                ->title(__('filament-panels::resources/pages/edit-record.notifications.error.title'))
                ->message(__('filament-panels::resources/pages/edit-record.notifications.error.message'))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('app_info')
                ->label(__('App info sync'))
                ->requiresConfirmation()
                ->modalDescription(__('Do you want to sync app data?'))
                ->modalIcon('heroicon-o-arrow-path')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $instance = filament()->getTenant();

                    if (! $instance->azure_app_id) {
                        Notification::make()
                            ->danger()
                            ->title(__('Azure App ID not set'))
                            ->body(__('Azure App ID not set'))
                            ->send();

                        return;
                    }

                    if (! $instance->university_member_id) {
                        Notification::make()
                            ->danger()
                            ->title(__('University member not set'))
                            ->body(__('University member not set'))
                            ->send();

                        return;
                    }

                    try {
                        AzureAppDataSyncJob::dispatchSync($instance);
                    } catch (Halt $exception) {
                        Notification::make()
                            ->title(__('Sync failed'))
                            ->body(__('Sync failed'))
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(__('Sync successful'))
                        ->body(__('Sync successful'))
                        ->send();
                }),
        ];
    }
}

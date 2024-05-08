<?php

namespace App\Filament\Admin\Custom\Actions\Table;

use App\Enums\Status;
use App\Services\ModuleApiService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EditInstanceAction
{
    public static function make(): EditAction
    {
        return EditAction::make()
            ->mutateRecordDataUsing(function (Model $record) {
                $record->use_existing_api_key = true;

                return $record->attributesToArray();
            })
            ->using(function (EditAction $action, Model $record, array $data): Model {
                try {
                    $apiKey = $data['use_existing_api_key'] === true ? Crypt::decrypt($record->api_key) : $data['api_key'];
                    $expirationDate = $data['use_existing_api_key'] === true ? dateToUnixOrNull($record->key_expiration_date) : dateToUnixOrNull($data['key_expiration_date']);
                    $moduleService = new ModuleApiService;
                    $responseTest = $moduleService->getInstanceData($data['url'], $apiKey);
                    $responseExpirationDate = $moduleService->postApiKeyStatus($data['url'], $apiKey, $expirationDate);
                    if (! $responseTest->ok() || $responseExpirationDate->status() !== 201) {
                        $exceptionMsg = (! $responseTest->ok()) ? 'Instance creation failed due to connection details misconfiguration!' : 'Api key expiration date was not set successfully';
                        throw new Exception($exceptionMsg);
                    }

                    if (isset($data['api_key'])) {
                        $data['api_key'] = Crypt::encrypt($data['api_key']);
                    }
                    $data['status'] = Status::Connected->value;
                    $record->update($data);
                } catch (Exception $exception) {
                    Log::error($exception->getMessage());
                    Notification::make()
                        ->danger()
                        ->title(__('Update failed!'))
                        ->body(__('Connection to external instance has failed. Check connection details or contact the administrator for more information.'))
                        ->send();

                    $action->halt();
                }

                return $record;
            })
            ->color('gray');
    }
}

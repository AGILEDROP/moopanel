<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AzureController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback()
    {
        $response = Socialite::driver('azure')->user();
        if (empty($response->id)) {
            Notification::make()
                ->title(__('User not found'))
                ->danger()
                ->send();

            return redirect()->route('filament.admin.auth.login');
        }

        $user = User::where('azure_id', $response->id)->first();
        if (empty($user)) {
            Notification::make()
                ->title(__('User not found'))
                ->danger()
                ->send();

            return redirect()->route('filament.admin.auth.login');
        }

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}

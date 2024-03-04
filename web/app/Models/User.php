<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'azure_id',
        'azure_token',
        'azure_access_token',
        'azure_refresh_token',
        'employee_id',
        'app_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'azure_id',
        'azure_token',
        'azure_access_token',
        'azure_refresh_token',
        'app_role_id',
        'employee_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function appRoleId(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $higestRole = null;
                $value->each(function ($role) use (&$higestRole) {
                    if (! empty($role['value'])) {
                        if (str_contains($role['value'], Role::MasterAdmin->value)) {
                            $higestRole = Role::MasterAdmin->value;
                        }
                        if ($higestRole !== Role::MasterAdmin->value && str_contains($role['value'], Role::User->value)) {
                            $higestRole = Role::User->value;
                        }
                    }
                });

                return $higestRole ?? null;
            },
            get: function ($value) {
                return collect($value);
            }
        );
    }
}

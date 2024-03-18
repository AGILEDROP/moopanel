<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
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
        'app_role_id',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'azure_id',
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

    public function appRoleId(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return collect($value);
            },
            set: function ($value) {
                $highestRole = null;
                $value->each(function ($role) use (&$highestRole) {
                    if (! empty($role['value'])) {
                        // Value should be json. Decode it into object/array.
                        $decoded = json_decode($role['value']);
                        // If decoded value exists set the highest role.
                        if (isset($decoded->value)) {
                            if ($decoded->value === Role::MasterAdmin->value) {
                                $highestRole = Role::MasterAdmin->value;
                            }
                            if ($highestRole !== Role::MasterAdmin->value && $decoded->value === Role::User->value) {
                                $highestRole = Role::User->value;
                            }
                        }
                    }
                });

                return $highestRole ?? null;
            }
        );
    }

    public function role(): ?Role
    {
        return Role::tryFrom($this->app_role_id->first());
    }

    // @todo: create observers to assign all universityMembers to user (userObserver, universityMemberObserver -> on create)!
    public function universityMembers(): MorphToMany
    {
        return $this->morphToMany(UniversityMember::class, 'memberable', 'university_memberables');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}

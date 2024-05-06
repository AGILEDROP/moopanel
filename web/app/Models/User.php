<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasTenants
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

    protected static function booted(): void
    {
        // Could also create the observer class for this!
        //@todo: ask who should have access to all instances instances (probably best to add access to roles)!
        static::created(function (User $user) {
            $user->instances()->attach(Instance::pluck('id')->toArray());
        });
    }

    //@todo: use spatie roles & permissions or custom roles table!
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

    public function universityMembers(): MorphToMany
    {
        return $this->morphToMany(UniversityMember::class, 'memberable', 'university_memberables');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function instances(): BelongsToMany
    {
        return $this->belongsToMany(Instance::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->instances;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->instances->contains($tenant);
    }
}

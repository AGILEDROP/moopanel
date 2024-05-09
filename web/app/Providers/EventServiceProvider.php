<?php

namespace App\Providers;

use App\Events\InstanceCreated;
use App\Listeners\SyncNewInstanceData;
use App\Models\Account;
use App\Models\UniversityMember;
use App\Models\User;
use App\Observers\AccountObserver;
use App\Observers\UniversityMemberObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        SocialiteWasCalled::class => [
            AzureExtendSocialite::class.'@handle',
        ],
        InstanceCreated::class => [
            SyncNewInstanceData::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Account::observe(AccountObserver::class);
        UniversityMember::observe(UniversityMemberObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

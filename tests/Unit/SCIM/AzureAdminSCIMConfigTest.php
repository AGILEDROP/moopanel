<?php

namespace Tests\Unit\SCIM;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\SCIM\AzureAdminSCIMConfig;
use ArieTimmerman\Laravel\SCIMServer\SCIMConfig;
use Tests\TestCase;

class AzureAdminSCIMConfigTest extends TestCase
{
    public function test_app_service_provider_registers_scim_config_singleton(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(AppServiceProvider::class, $providers);
        $this->assertInstanceOf(AzureAdminSCIMConfig::class, $this->app->make(SCIMConfig::class));
    }

    public function test_scim_config_exposes_users_resource_definition(): void
    {
        $config = $this->app->make(SCIMConfig::class)->getConfig();

        $this->assertArrayHasKey('Users', $config);
        $this->assertSame(User::class, $config['Users']['class']);
        $this->assertSame('User', $config['Users']['singular']);
        $this->assertSame('User Account', $config['Users']['description']);
        $this->assertIsCallable($config['Users']['factory']);
    }

    public function test_scim_user_factory_creates_user_with_password(): void
    {
        $config = $this->app->make(SCIMConfig::class)->getConfig();
        $user = $config['Users']['factory']();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->password);
    }
}

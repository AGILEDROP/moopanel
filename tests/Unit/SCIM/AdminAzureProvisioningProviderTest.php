<?php

namespace Tests\Unit\SCIM;

use App\SCIM\src\AdminAzureProvisioningProvider;
use App\SCIM\src\Resources\UsersResourceType;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use RobTrehy\LaravelAzureProvisioning\Exceptions\AzureProvisioningException;
use Tests\TestCase;

class AdminAzureProvisioningProviderTest extends TestCase
{
    public function test_provider_is_registered_in_application(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(AdminAzureProvisioningProvider::class, $providers);
    }

    public function test_register_merges_config_under_azureprovisioning_admin_namespace(): void
    {
        // mergeConfigFrom keeps any pre-existing app-level overrides; we only assert that the
        // package-only keys (singular, Groups.model) end up in the merged config.
        $this->assertNotNull(config('azureprovisioning-admin.routePrefix'));
        $this->assertSame('User', config('azureprovisioning-admin.Users.singular'));
        $this->assertSame('Group', config('azureprovisioning-admin.Groups.singular'));
        $this->assertSame(\Spatie\Permission\Models\Role::class, config('azureprovisioning-admin.Groups.model'));
    }

    public function test_boot_registers_scim_routes_under_configured_prefix(): void
    {
        $resourcesRoute = Route::getRoutes()->getByName('AdminAzureProvisioning.Resources');
        $this->assertNotNull($resourcesRoute);
        $this->assertStringStartsWith(config('azureprovisioning-admin.routePrefix'), $resourcesRoute->uri());
    }

    public function test_overwritten_resource_type_binder_throws_404_for_unknown_resource(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $callback = $router->getBindingCallback('overwrittenResourceType');

        $this->assertNotNull($callback, 'Expected overwrittenResourceType binder to be registered.');

        try {
            $callback('unknown-resource');
            $this->fail('Expected AzureProvisioningException for missing config.');
        } catch (AzureProvisioningException $e) {
            $this->assertStringContainsString('unknown-resource', $e->getMessage());
        }
    }

    public function test_overwritten_resource_type_binder_returns_resource_for_known_name(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $callback = $router->getBindingCallback('overwrittenResourceType');

        $resource = $callback('Users');

        $this->assertInstanceOf(UsersResourceType::class, $resource);
    }

    public function test_overwritten_resource_object_binder_throws_when_no_resource_type_provided(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $callback = $router->getBindingCallback('overwrittenResourceObject');

        $this->assertNotNull($callback);

        $route = new \Illuminate\Routing\Route(['GET'], '/test', []);
        $route->bind(\Illuminate\Http\Request::create('/test'));

        try {
            $callback('123', $route);
            $this->fail('Expected AzureProvisioningException for missing resource type.');
        } catch (AzureProvisioningException $e) {
            $this->assertSame('ResourceType not provided', $e->getMessage());
        }
    }
}

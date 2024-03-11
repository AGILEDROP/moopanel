<?php

namespace App\SCIM\src;

use App\SCIM\src\Utils\AzureHelper;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use RobTrehy\LaravelAzureProvisioning;
use RobTrehy\LaravelAzureProvisioning\Exceptions\AzureProvisioningException;

class AccountsAzureProvisioningProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/azureprovisioning-accounts.php',
            'azureprovisioning-accounts'
        );

        //  $this->app->singleton(LaravelAzureProvisioning\Utils\AzureHelper::class, AzureHelper::class);
        //  $this->app->singleton(LaravelAzureProvisioning\Resources\ResourceType::class, \App\SCIM\src\Resources\ResourceType::class);
        //  $this->app->singleton(LaravelAzureProvisioning\SCIM\ResourceType::class, \App\SCIM\src\SCIM\ResourceType::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->publishes(
            [__DIR__.'/Config/azureprovisioning-accounts.php' => config_path('azureprovisioning-accounts.php')],
            'azureprovisioning-accounts'
        );

        $router->bind(
            'overwrittenResourceType',
            function ($name) {
                $config = config('azureprovisioning-accounts.'.$name);

                if ($config === null) {
                    throw (new AzureProvisioningException(sprintf('No resource %s found.', $name)))->setCode(404);
                }

                $resourceType = "App\SCIM\src\Resources\\".$name.'ResourceType';

                return new $resourceType($name, $config);
            }
        );

        $router->bind(
            'overwrittenResourceObject',
            function ($id, $route) {
                $resourceType = $route->parameter('overwrittenResourceType');

                if (! $resourceType) {
                    throw (new AzureProvisioningException('ResourceType not provided'))->setCode(404);
                }

                $model = $resourceType->getModel();
                $resourceObject = $model::find($id);

                if ($resourceObject === null) {
                    throw (new AzureProvisioningException(sprintf('Resource %s not found', $id)))->setCode(404);
                }

                if (($matchIf = \request()->header('IF-match'))) {
                    $versionsAllowed = preg_split('/\s*,\s*/', $matchIf);
                    $currentVersion = AzureHelper::getResourceObjectVersion($resourceObject);

                    if (! in_array($currentVersion, $versionsAllowed) && ! in_array('*', $versionsAllowed)) {
                        throw (new AzureProvisioningException('Failed to update. Resource changed on the server.'))
                            ->setCode(412);
                    }
                }

                return $resourceObject;
            }
        );

        $this->loadRoutesFrom(__DIR__.'/Routes/scim.php');
    }
}

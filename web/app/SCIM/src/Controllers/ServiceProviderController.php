<?php

namespace App\SCIM\src\Controllers;

use RobTrehy\LaravelAzureProvisioning\Controllers\ServiceProviderController as OriginalServiceProviderController;

class ServiceProviderController extends OriginalServiceProviderController
{
    public function index()
    {
        $results = parent::index();
        $results['meta']['location'] = config('azureprovisioning-accounts.routes.ServiceProviderConfig');

        return $results;
    }
}

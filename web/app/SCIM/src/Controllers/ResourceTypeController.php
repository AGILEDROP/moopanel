<?php

namespace App\SCIM\src\Controllers;

use App\SCIM\src\SCIM\ResourceType;
use Illuminate\Routing\Controller;

class ResourceTypeController extends Controller
{
    private $resourceTypes = null;

    public function __construct()
    {
        $resourceTypes = [];

        foreach (config('azureprovisioning-admin') as $type => $settings) {
            if (isset($settings['schema'])) {
                $resourceTypes[] = new ResourceType(
                    $settings['singular'],
                    $type,
                    $type,
                    $settings['description'],
                    $settings['schema']
                );
            }
        }

        $this->resourceTypes = collect($resourceTypes);
    }

    public function index()
    {
        return $this->resourceTypes;
    }
}

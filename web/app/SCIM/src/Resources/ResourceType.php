<?php

namespace App\SCIM\src\Resources;

use RobTrehy\LaravelAzureProvisioning\Resources\ResourceType as OriginalResourceType;

class ResourceType extends OriginalResourceType
{
    public function user()
    {
        return new UsersResourceType('Users', config('azureprovisioning-accounts.Users'));
    }

    public function group()
    {
        return new GroupsResourceType('Groups', config('azureprovisioning-accounts.Groups'));
    }
}

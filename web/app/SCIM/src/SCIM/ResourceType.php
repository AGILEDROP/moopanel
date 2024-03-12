<?php

namespace App\SCIM\src\SCIM;

use Illuminate\Contracts\Support\Jsonable;
use RobTrehy\LaravelAzureProvisioning\SCIM\ResourceType as OriginalResourceType;
use RobTrehy\LaravelAzureProvisioning\Utils\SCIMConstantsV2;

class ResourceType extends OriginalResourceType implements Jsonable
{
    public function toArray()
    {
        return [
            'schemas' => [SCIMConstantsV2::SCHEMA_RESOURCE_TYPE],
            'id' => $this->id,
            'name' => $this->name,
            'endpoint' => route('AdminAzureProvisioning.Resources', ['overwrittenResourceType' => $this->type]),
            'description' => $this->description,
            'schema' => $this->schema,
            'schemaExtensions' => $this->schemaExtensions,
            'meta' => [
                'location' => route('AdminAzureProvisioning.ResourceType', ['id' => $this->id]),
                'resourceType' => 'ResourceType',
            ],
        ];
    }
}

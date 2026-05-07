<?php

namespace App\SCIM\src\Utils;

abstract class SCIMConstantsV2
{
    public const SCHEMA_USER = 'urn:ietf:params:scim:schemas:core:2.0:User';
    public const SCHEMA_ENTERPRISE_USER = 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User';
    public const SCHEMA_GROUP = 'urn:ietf:params:scim:schemas:core:2.0:Group';
    public const SCHEMA_SERVICE_PROVIDER_CONFIG = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';
    public const SCHEMA_RESOURCE_TYPE = 'urn:ietf:params:scim:schemas:core:2.0:ResourceType';
    public const SCHEMA_SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:Schema';
    public const SCHEMA_ERROR = 'urn:ietf:params:scim:api:messages:2.0:Error';
    public const MESSAGE_BULK_REQUEST = 'urn:ietf:params:scim:api:messages:2.0:BulkRequest';
    public const MESSAGE_BULK_RESPONSE = 'urn:ietf:params:scim:api:messages:2.0:BulkResponse';
    public const MESSAGE_PATCH_OP = 'urn:ietf:params:scim:api:messages:2.0:PatchOp';
    public const MESSAGE_LIST_RESPONSE = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';
    public const MESSAGE_SEARCH_REQUEST = 'urn:ietf:params:scim:api:messages:2.0:SearchRequest';
}

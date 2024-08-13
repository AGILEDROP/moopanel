<?php

return [
    'azure_client_id' => env('AZURE_CLIENT_ID'),
    'azure_client_secret' => env('AZURE_CLIENT_SECRET'),
    'azure_redirect_uri' => env('AZURE_REDIRECT_URI'),
    'azure_tenant_id' => env('AZURE_TENANT_ID'),
    'azure_app_resource_id' => env('AZURE_APP_RESOURCE_ID'),
    'azure_oauth2_scope' => env('AZURE_OAUTH2_SCOPE'),
    'azure_client_assertion_type' => env('AZURE_CLIENT_ASSERTION_TYPE'),
    'azure_sis_client_id' => env('AZURE_SIS_CLIENT_ID'),

    'scim_secret_token_users' => env('SCIM_SECRET_TOKEN_USERS'),
    'scim_secret_token_accounts' => env('SCIM_SECRET_TOKEN_ACCOUNTS'),

    'sis_api_key_name' => env('SIS_API_KEY_NAME'),
    'sis_api_key_value' => env('SIS_API_KEY_VALUE'),

    'sis_api_jwt_private_key' => env('SIS_API_PRIVATE_KEY_PATH'),
    'sis_api_jwt_alg' => env('SIS_JWT_ALG'),
    'sis_api_jwt_x5t' => env('SIS_JWT_X5T'),
    'sis_api_jwt_iss' => env('SIS_JWT_ISS'),
    'sis_api_jwt_sub' => env('SIS_JWT_SUB'),
    'sis_api_jwt_aud' => env('SIS_JWT_AUD'),
    'sis_api_jwt_expiration' => env('SIS_JWT_EXPIRATION'),
];

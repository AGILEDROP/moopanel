<?php

use App\Http\Middleware\SCIM\AccountsSecretTokenMiddleware;
use App\Models\Account;
use RobTrehy\LaravelAzureProvisioning\Utils\SCIMConstantsV2;

return [

    /**
     * Set the prefix for the SCIM service routes
     */
    'routePrefix' => 'scim/v2',
    'routeMiddleware' => [
        AccountsSecretTokenMiddleware::class,
    ],

    /**
     * User Resource Type settings
     */
    'Users' => [
        'singular' => 'User',
        'description' => 'User Account',
        'schema' => [SCIMConstantsV2::SCHEMA_ENTERPRISE_USER, SCIMConstantsV2::SCHEMA_USER],

        /**
         * Specifiy the model that should be used.
         *
         * Default: config('auth.providers.users.model')
         * Default: App\Models\User::class
         */
        'model' => Account::class,

        /**
         * Request validation rules.
         * All fields that you wish to map to an attribute must
         * be included in the validation or it will be ignored.
         */
        'validations' => [
            'externalid' => 'required',
            'username' => 'required',
            'displayname' => 'required',
            'emails' => 'required|array',
        ],

        /**
         * Specify relations to eager load
         */
        'relations' => [],

        /**
         * Specifiy which attributes should not be included in a response.
         *
         * Default: ['password']
         */
        'exclude' => [],

        /**
         * Specify default values for attributes that are not nullable.
         */
        'defaults' => [],

        /**
         * Declare the SCIM attributes to map to your Model attributes.
         *
         * Required Fields: id
         */
        'mapping' => [
            'id' => 'id',
            'externalid' => 'azure_id',
            'username' => 'username',
            'displayname' => 'name',
            'password' => 'password',
            'emails.work.value' => 'email',
        ],
    ],
];

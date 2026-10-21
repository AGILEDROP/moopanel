<?php

namespace App\SCIM;

use App\Models\User;
use App\SCIM\Attributes\UserRolesAttribute;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Attribute;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Complex;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Constant;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Eloquent;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Meta;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Schema as AttributeSchema;
use ArieTimmerman\Laravel\SCIMServer\SCIM\Schema;
use ArieTimmerman\Laravel\SCIMServer\SCIMConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use function ArieTimmerman\Laravel\SCIMServer\complex;

class AzureSCIMConfig extends SCIMConfig
{
    public const string USERS_RESOURCE = 'Users';

    public function getConfig(): array
    {
        return [
            'Users' => $this->getUserConfig(),
        ];
    }

    public function getUserConfig(): array
    {
        $config = parent::getUserConfig();

        $config['class'] = User::class;
        $config['factory'] = function () {
            $user = new User();
            $user->password = Hash::make(Str::random(32));

            return $user;
        };
        $config['map'] = complex()->withSubAttributes(
            new class('schemas', [Schema::SCHEMA_USER]) extends Constant {
                public function replace($value, &$object, $path = null)
                {
                    $this->dirty = true;
                }
            },
            new class('id', null) extends Constant {
                protected function doRead(&$object, $attributes = [])
                {
                    return (string) $object->id;
                }

                public function remove($value, &$object, $path = null)
                {
                    // do nothing
                }
            },
            new Meta(self::USERS_RESOURCE),
            new AttributeSchema(Schema::SCHEMA_USER, true)->withSubAttributes(
                new Eloquent('externalId', 'azure_id')->ensure('required'),
                new Eloquent('userName', 'username')->ensure('required'),
                new Complex('name')->withSubAttributes(
                    new Eloquent('formatted', 'name')->ensure('required')
                ),
                new Eloquent('displayName', 'name')->ensure('required'),
                new Eloquent('password')->ensure('nullable')->setReturned('never'),
                new class('title') extends Complex {
                    protected function doRead(&$object, $attributes = [])
                    {
                        // employee_id is an integer column, but SCIM "title" must be a string.
                        return $object->employee_id === null ? null : (string) $object->employee_id;
                    }

                    public function add($value, Model &$object)
                    {
                        $object->employee_id = $value;
                    }

                    public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
                    {
                        $object->employee_id = $value;
                    }
                }->ensure('nullable'),
                new Eloquent('active')->ensure('boolean')->default(true),
                new class('emails') extends Complex {
                    protected function doRead(&$object, $attributes = [])
                    {
                        return collect([$object->email])->map(function ($email) {
                            return [
                                'value' => $email,
                                'type' => 'work',
                                'primary' => true,
                            ];
                        })->toArray();
                    }

                    public function add($value, Model &$object)
                    {
                        $object->email = $value[0]['value'];
                    }

                    public function replace($value, Model &$object, $path = null, $removeIfNotSet = false)
                    {
                        $object->email = $value[0]['value'];
                    }
                }->withSubAttributes(
                    new Eloquent('value', 'email')->ensure('required', 'email'),
                    new Constant('type', 'work'),
                    new Constant('primary', true)
                )->ensure('required', 'array')->setMultiValued(true),
                new UserRolesAttribute()
                    ->withSubAttributes(
                        new Attribute('value')->ensure('required'),
                        new Attribute('display')->ensure('nullable'),
                        new Constant('primary', true)
                    )
                    ->ensure('required', 'array')
                    ->setMultiValued(true),
                new AttributeSchema('urn:ietf:params:scim:schemas:extension:enterprise:2.0:User', false)->withSubAttributes(
                    new Eloquent('employeeNumber', 'employee_id')->ensure('nullable')
                )
            ),
        );

        return $config;
    }
}

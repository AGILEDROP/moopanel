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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AzureAdminSCIMConfig extends SCIMConfig
{
    public function getUserConfig()
    {
        return [
            'class' => User::class,
            'singular' => 'User',
            'withRelations' => [],
            'description' => 'User Account',
            'factory' => function () {
                $user = new User();
                $user->password = Hash::make(Str::random(32));

                return $user;
            },
            'map' => $this->buildUserMap(),
        ];
    }

    protected function buildUserMap(): Complex
    {
        return $this->root()->withSubAttributes(
            $this->schemasConstant([Schema::SCHEMA_USER]),
            $this->idConstant(),
            new Meta('Users'),
            (new AttributeSchema(Schema::SCHEMA_USER, true))->withSubAttributes(
                new Eloquent('userName', 'username')->ensure('required'),
                (new Eloquent('externalId', 'azure_id'))->ensure('required'),
                new Eloquent('active')->ensure('boolean')->default(true),
                (new Complex('name'))->withSubAttributes(
                    new Eloquent('formatted', 'name')->ensure('required')
                ),
                new Eloquent('displayName', 'name')->ensure('required'),
                new Eloquent('password')->ensure('nullable')->setReturned('never'),
                (new class ('emails') extends Complex {
                    protected function doRead(&$object, $attributes = [])
                    {
                        return [[
                            'value' => $object->email,
                            'type' => 'work',
                            'primary' => true,
                        ]];
                    }

                    public function add($value, \Illuminate\Database\Eloquent\Model &$object)
                    {
                        $object->email = $value[0]['value'] ?? null;
                    }

                    public function replace($value, \Illuminate\Database\Eloquent\Model &$object, $path = null, $removeIfNotSet = false)
                    {
                        $object->email = $value[0]['value'] ?? null;
                    }
                })
                    ->withSubAttributes(
                        new Eloquent('value', 'email')->ensure('required', 'email'),
                        new Constant('type', 'work'),
                        new Constant('primary', true)
                    )
                    ->ensure('required', 'array')
                    ->setMultiValued(true),
                (new UserRolesAttribute())
                    ->withSubAttributes(
                        (new Attribute('value'))->ensure('required'),
                        (new Attribute('display'))->ensure('nullable'),
                        new Constant('primary', true)
                    )
                    ->ensure('required', 'array')
                    ->setMultiValued(true),
                new Eloquent('title', 'employee_id')->ensure('nullable')
            ),
            (new AttributeSchema('urn:ietf:params:scim:schemas:extension:enterprise:2.0:User', false))->withSubAttributes(
                new Eloquent('employeeNumber', 'employee_id')->ensure('nullable')
            )
        );
    }

    protected function root(): Complex
    {
        return new Complex();
    }

    protected function schemasConstant(array $schemas): Constant
    {
        return new class ('schemas', $schemas) extends Constant {
            public function replace($value, &$object, $path = null)
            {
                $this->dirty = true;
            }
        };
    }

    protected function idConstant(): Constant
    {
        return new class ('id', null) extends Constant {
            protected function doRead(&$object, $attributes = [])
            {
                return (string) $object->id;
            }

            public function remove($value, &$object, $path = null)
            {
            }
        };
    }

    public function getConfig()
    {
        return [
            'Users' => $this->getUserConfig(),
        ];
    }
}

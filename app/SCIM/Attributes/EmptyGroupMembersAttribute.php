<?php

namespace App\SCIM\Attributes;

use ArieTimmerman\Laravel\SCIMServer\Attribute\Collection;
use ArieTimmerman\Laravel\SCIMServer\Attribute\Constant;

class EmptyGroupMembersAttribute extends Collection
{
    public function __construct(string $name = 'members')
    {
        parent::__construct($name);

        $this->setMutability('readOnly')
            ->withSubAttributes(
                new Constant('value', null),
                new Constant('$ref', null),
                new Constant('display', null),
            );
    }

    protected function doRead(&$object, $attributes = [])
    {
        return [];
    }
}

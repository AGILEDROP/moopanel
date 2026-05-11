<?php

namespace App\SCIM\Attributes;

use App\Enums\Role;
use ArieTimmerman\Laravel\SCIMServer\Attribute\JSONCollection;
use ArieTimmerman\Laravel\SCIMServer\Parser\Path;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserRolesAttribute extends JSONCollection
{
    public function __construct(string $name = 'roles', string $attribute = 'app_role_id')
    {
        parent::__construct($name, $attribute);
    }

    public function doRead(&$object, $attributes = [])
    {
        $value = $object->getAttributes()[$this->attribute] ?? null;

        if (blank($value)) {
            return [];
        }

        $role = Role::tryFrom((string) $value);

        return [[
            'value' => (string) $value,
            'display' => $role?->toReadableString(),
            'primary' => true,
        ]];
    }

    public function add($value, Model &$object)
    {
        $object->{$this->attribute} = $this->buildRolePayload($value, $object);
    }

    public function replace($value, Model &$object, ?Path $path = null)
    {
        $object->{$this->attribute} = $this->buildRolePayload($value, $object);
    }

    public function remove($value, Model &$object, ?Path $path = null)
    {
        if ($path?->getValuePathFilter()?->getComparisonExpression() !== null) {
            $compareValue = $path->getValuePathFilter()->getComparisonExpression()->compareValue;

            if ((string) ($object->getAttributes()[$this->attribute] ?? null) === (string) $compareValue) {
                $object->{$this->attribute} = collect();
            }

            return;
        }

        $object->{$this->attribute} = collect();
    }

    protected function buildRolePayload(array $value, Model $object): Collection
    {
        $roles = collect($value)
            ->map(fn (array $item) => $this->normalizeRoleValue($item['value'] ?? null))
            ->filter();

        $selectedRole = null;

        if ($roles->contains(Role::MasterAdmin->value)) {
            $selectedRole = Role::MasterAdmin->value;
        } elseif ($roles->contains(Role::User->value)) {
            $selectedRole = Role::User->value;
        } else {
            $selectedRole = $object->getAttributes()[$this->attribute] ?? null;
        }

        if (blank($selectedRole)) {
            return collect();
        }

        return collect([[
            'value' => json_encode(['value' => (string) $selectedRole]),
        ]]);
    }

    protected function normalizeRoleValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $nested = $decoded['value'] ?? null;

            return is_scalar($nested) ? (string) $nested : null;
        }

        return $value;
    }
}

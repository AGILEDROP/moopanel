<?php

namespace App\SCIM\src\SCIM;

use App\SCIM\src\Utils\SCIMConstantsV2;
use Illuminate\Contracts\Support\Jsonable;

class Error implements Jsonable
{
    protected string $detail;

    protected string|int $status;

    protected string $SCIMType;

    protected array $errors = [];

    public function __construct(string $detail, string|int $status = '404', string $SCIMType = 'invalidValue')
    {
        $this->detail = $detail;
        $this->status = $status;
        $this->SCIMType = $SCIMType;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function toJson($options = 0)
    {
        return json_encode(
            array_filter([
                'schemas' => [SCIMConstantsV2::SCHEMA_ERROR],
                'detail' => $this->detail,
                'status' => $this->status,
                'scimType' => ($this->status === 400 ? $this->SCIMType : null),
                'error' => $this->errors,
            ]),
            $options
        );
    }
}

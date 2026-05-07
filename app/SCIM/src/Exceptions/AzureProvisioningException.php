<?php

namespace App\SCIM\src\Exceptions;

use App\SCIM\src\SCIM\Error;
use Exception;
use Illuminate\Support\Facades\Log;

class AzureProvisioningException extends Exception
{
    protected string $SCIMType = 'invalidValue';

    protected int $httpCode = 404;

    protected array $errors = [];

    public function setSCIMType(string $SCIMType): self
    {
        $this->SCIMType = $SCIMType;

        return $this;
    }

    public function setCode($code): self
    {
        $this->httpCode = (int) $code;

        return $this;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function report(): void
    {
        Log::debug(sprintf(
            "Validation failed. Errors: %s\n\nMessage: %s\n\nBody: %s",
            json_encode($this->errors, JSON_PRETTY_PRINT),
            $this->getMessage(),
            request()->getContent()
        ));
    }

    public function render()
    {
        return response(
            (new Error($this->getMessage(), $this->httpCode, $this->SCIMType))->setErrors($this->errors),
            $this->httpCode
        );
    }
}

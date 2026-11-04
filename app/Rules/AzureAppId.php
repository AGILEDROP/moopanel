<?php

namespace App\Rules;

use Illuminate\Translation\PotentiallyTranslatedString;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AzureAppId implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $value)) {
            $fail('The :attribute is not a valid Azure App ID(GUID format).');
        }
    }
}

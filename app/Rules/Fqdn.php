<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Fqdn implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        if ($value === 'localhost') {
            $fail('validation.no_loopback')->translate();

            return;
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            if (request()->isSecure()) {
                $fail('validation.fqdn')->translate();

                return;
            }
            if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
                $fail('validation.no_loopback')->translate();
            }

            return;
        }

        $fail('validation.ip')->translate();
    }
}

<?php

namespace App\Modules\User\Rules;

use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ColombianPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (ColombianPhoneNumberFormatter::normalize($value) === null) {
            $fail('El número de teléfono debe ser un celular colombiano, por ejemplo +57 300 123 4567.');
        }
    }
}

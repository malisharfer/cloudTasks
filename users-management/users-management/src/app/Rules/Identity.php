<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Identity implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $id = str_pad($value, 9, '0', STR_PAD_LEFT);
        if (!preg_match('/^[0-9]{9}$/', $id))
            $fail(__('The :attribute is invalid.'));
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $digit = (int) $id[$i];
            $sum += ($i % 2 === 0) ? $digit : array_sum(str_split($digit * 2));
        }
        if ($sum % 10 > 0)
        {
            $fail(__('The :attribute is invalid.'));
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBankCardNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $number = preg_replace('/\D/', '', (string) $value);

        if (! preg_match('/^\d{13,19}$/', $number)) {
            $fail('Banko kortelės numeris turi būti 13–19 skaitmenų.');

            return;
        }

        if (! $this->passesLuhn($number)) {
            $fail('Neteisingas banko kortelės numeris.');
        }
    }

    private function passesLuhn(string $number): bool
    {
        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }
}

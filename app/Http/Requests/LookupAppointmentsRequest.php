<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class LookupAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Closure|string>>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'bail',
                'required',
                'string',
                'max:32',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $countryCode = $this->input('phone_country_code');

                    if (! PhoneNumber::isValid(is_string($countryCode) ? $countryCode : null, (string) $value)) {
                        $fail('Enter a valid phone number.');
                    }
                },
            ],
            'phone_country_code' => ['nullable', 'string', 'max:8', 'regex:/^\+[1-9][0-9]{0,3}$/'],
        ];
    }
}

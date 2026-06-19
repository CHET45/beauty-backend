<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            // Optional. When given, the (service, specialist) pair must exist in
            // the pivot — a booking can never reference a specialist who does
            // not offer the chosen service.
            'specialist_id' => [
                'nullable',
                'integer',
                Rule::exists('service_specialist', 'specialist_id')
                    ->where('service_id', $this->input('service_id')),
            ],
            'customer_name' => ['required', 'string', 'max:120'],
            'phone_country_code' => ['bail', 'required', 'string', 'max:8', 'regex:/^\+[1-9][0-9]{0,3}$/'],
            'customer_phone' => [
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
            'customer_email' => ['nullable', 'email:rfc', 'max:255'],
            'starts_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'phone_country_code' => ['required', 'string', 'max:8', 'regex:/^\+[0-9]{1,4}$/'],
            'customer_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9\s().-]{4,32}$/'],
            'customer_email' => ['nullable', 'email:rfc', 'max:255'],
            'starts_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

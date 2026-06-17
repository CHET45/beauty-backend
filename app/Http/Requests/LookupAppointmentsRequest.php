<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupAppointmentsRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:32'],
            'phone_country_code' => ['nullable', 'string', 'max:8'],
        ];
    }
}

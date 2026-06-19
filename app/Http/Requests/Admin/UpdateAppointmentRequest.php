<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Admins may make an explicit exception to the normal service list.
            'specialist_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('specialists', 'id')->where('is_active', true),
            ],
        ];
    }
}

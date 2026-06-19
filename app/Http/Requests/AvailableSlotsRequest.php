<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailableSlotsRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            // Optional: omit for "any" (pool-wide) availability, or pass a
            // specialist who offers this service for their own calendar.
            'specialist_id' => [
                'nullable',
                'integer',
                Rule::exists('service_specialist', 'specialist_id')
                    ->where('service_id', $this->input('service_id')),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}

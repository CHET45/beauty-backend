<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAppointmentsRequest extends FormRequest
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
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'phone' => ['nullable', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:120'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'sort' => ['nullable', Rule::in(['starts_at', 'customer_name', 'status', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}

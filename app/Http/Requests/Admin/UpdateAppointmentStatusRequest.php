<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::enum(AppointmentStatus::class),
                function (string $attribute, mixed $value, Closure $fail): void {
                    /** @var Appointment $appointment */
                    $appointment = $this->route('appointment');
                    if (
                        in_array($value, [
                            AppointmentStatus::Confirmed->value,
                            AppointmentStatus::Completed->value,
                        ], true) && $appointment->specialist_id === null
                    ) {
                        $fail('Assign a specialist before confirming or completing this booking.');
                    }
                },
            ],
        ];
    }
}

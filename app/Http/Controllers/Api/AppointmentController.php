<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LookupAppointmentsRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentScheduler;

class AppointmentController extends Controller
{
    /**
     * Public lookup: anyone can list the appointments tied to a phone number.
     * No authentication by design — this is a convenience feature, not protected data.
     */
    public function index(LookupAppointmentsRequest $request)
    {
        $validated = $request->validated();
        $localDigits = preg_replace('/\D+/', '', $validated['phone']);

        $appointments = Appointment::query()
            ->with('service')
            ->where('customer_phone', $localDigits)
            ->when(
                ! empty($validated['phone_country_code']),
                fn ($query) => $query->where('phone_country_code', $validated['phone_country_code'])
            )
            ->orderByDesc('starts_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function store(StoreAppointmentRequest $request, AppointmentScheduler $scheduler)
    {
        $appointment = $scheduler->book($request->validated());

        return response()->json([
            'message' => 'Appointment created.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }
}

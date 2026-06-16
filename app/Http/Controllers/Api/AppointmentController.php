<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentScheduler;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, AppointmentScheduler $scheduler)
    {
        $appointment = $scheduler->book($request->validated());

        return response()->json([
            'message' => 'Appointment created.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }
}

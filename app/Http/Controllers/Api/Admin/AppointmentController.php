<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAppointmentsRequest;
use App\Http\Requests\Admin\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AppointmentController extends Controller
{
    public function index(ListAppointmentsRequest $request)
    {
        $validated = $request->validated();

        $appointments = Appointment::query()
            ->with('service')
            ->when(
                ! empty($validated['date']),
                fn ($query) => $query->whereDate('starts_at', $validated['date'])
            )
            ->orderBy('starts_at');

        return AppointmentResource::collection($appointments->get());
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validated();

        $appointment->update([
            'status' => $validated['status'],
        ]);

        $appointment->load('service');

        return response()->json([
            'message' => 'Appointment status updated.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

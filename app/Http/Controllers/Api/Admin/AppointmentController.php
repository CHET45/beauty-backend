<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppointmentRequest;
use App\Http\Requests\Admin\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AppointmentController extends Controller
{
    // Returns every appointment; filtering and sorting are handled client-side
    // in the admin app so they apply instantly without round-tripping the API.
    public function index()
    {
        $appointments = Appointment::query()
            ->with(['service', 'specialist'])
            ->orderByDesc('starts_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validated();

        $appointment->update([
            'status' => $validated['status'],
        ]);

        $appointment->load(['service', 'specialist']);

        return response()->json([
            'message' => 'Appointment status updated.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    // Assign or change the specialist on a booking. Admins can choose an active
    // specialist outside the normal service list as an explicit exception.
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update([
            'specialist_id' => $request->validated()['specialist_id'],
        ]);

        $appointment->load(['service', 'specialist']);

        return response()->json([
            'message' => 'Appointment updated.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

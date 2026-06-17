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

        $sort = $validated['sort'] ?? 'starts_at';
        $direction = $validated['direction'] ?? 'asc';

        $appointments = Appointment::query()
            ->with('service')
            ->when(
                ! empty($validated['date']),
                fn ($query) => $query->whereDate('starts_at', $validated['date'])
            )
            ->when(
                ! empty($validated['date_from']),
                fn ($query) => $query->whereDate('starts_at', '>=', $validated['date_from'])
            )
            ->when(
                ! empty($validated['date_to']),
                fn ($query) => $query->whereDate('starts_at', '<=', $validated['date_to'])
            )
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->when(
                ! empty($validated['name']),
                fn ($query) => $query->where('customer_name', 'like', '%'.trim($validated['name']).'%')
            )
            ->when(
                ! empty($validated['service_id']),
                fn ($query) => $query->where('service_id', $validated['service_id'])
            )
            ->when(
                ! empty($validated['phone']) && preg_replace('/\D+/', '', $validated['phone']) !== '',
                fn ($query) => $query->where(
                    'customer_phone',
                    'like',
                    '%'.preg_replace('/\D+/', '', $validated['phone']).'%'
                )
            )
            ->orderBy($sort, $direction)
            // Stable secondary order so equal keys keep a predictable sequence.
            ->orderBy('starts_at')
            ->get();

        return AppointmentResource::collection($appointments);
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

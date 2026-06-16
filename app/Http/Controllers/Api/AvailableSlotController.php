<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableSlotsRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\AppointmentScheduler;
use Carbon\CarbonImmutable;

class AvailableSlotController extends Controller
{
    public function __invoke(AvailableSlotsRequest $request, AppointmentScheduler $scheduler)
    {
        $validated = $request->validated();

        $service = Service::query()
            ->active()
            ->findOrFail($validated['service_id']);

        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date']);

        return response()->json([
            'data' => [
                'date' => $date->format('Y-m-d'),
                'service' => new ServiceResource($service),
                'slots' => $scheduler->availableSlots($service, $date),
            ],
        ]);
    }
}

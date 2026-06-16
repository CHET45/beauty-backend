<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    private const WORK_START_HOUR = 9;

    private const WORK_END_HOUR = 18;

    private const SLOT_STEP_MINUTES = 30;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableSlots(Service $service, CarbonInterface $date): array
    {
        $workStart = CarbonImmutable::instance($date)->setTime(self::WORK_START_HOUR, 0);
        $workEnd = CarbonImmutable::instance($date)->setTime(self::WORK_END_HOUR, 0);

        $appointments = Appointment::query()
            ->notCancelled()
            ->where('starts_at', '<', $workEnd)
            ->where('ends_at', '>', $workStart)
            ->get(['starts_at', 'ends_at']);

        $slots = [];
        $slotStart = $workStart;

        while ($slotStart->addMinutes($service->duration_minutes)->lessThanOrEqualTo($workEnd)) {
            $slotEnd = $slotStart->addMinutes($service->duration_minutes);

            $hasConflict = $appointments->contains(
                fn (Appointment $appointment): bool => $appointment->starts_at->lessThan($slotEnd)
                    && $appointment->ends_at->greaterThan($slotStart)
            );

            $slots[] = [
                'time' => $slotStart->format('H:i'),
                'starts_at' => $slotStart->toIso8601String(),
                'ends_at' => $slotEnd->toIso8601String(),
                'available' => ! $hasConflict,
            ];

            $slotStart = $slotStart->addMinutes(self::SLOT_STEP_MINUTES);
        }

        return $slots;
    }

    /**
     * @param  array{service_id:int,customer_name:string,customer_phone:string,starts_at:string,notes?:string|null}  $payload
     */
    public function book(array $payload): Appointment
    {
        return DB::transaction(function () use ($payload): Appointment {
            /** @var Service $service */
            $service = Service::query()
                ->whereKey($payload['service_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $service->is_active) {
                throw ValidationException::withMessages([
                    'service_id' => 'Selected service is not available.',
                ]);
            }

            $startsAt = CarbonImmutable::parse($payload['starts_at'], config('app.timezone'));
            $endsAt = $startsAt->addMinutes($service->duration_minutes);

            $this->ensureBookableTime($startsAt, $endsAt);

            Appointment::query()
                ->where('starts_at', '>=', $startsAt->startOfDay())
                ->where('starts_at', '<', $startsAt->addDay()->startOfDay())
                ->lockForUpdate()
                ->get(['id']);

            $hasConflict = Appointment::query()
                ->notCancelled()
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'This time slot is already booked.',
                ]);
            }

            return Appointment::create([
                'service_id' => $service->id,
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Pending,
                'notes' => $payload['notes'] ?? null,
            ])->load('service');
        });
    }

    private function ensureBookableTime(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Appointment start time must be in the future.',
            ]);
        }

        if ($startsAt->minute % self::SLOT_STEP_MINUTES !== 0 || $startsAt->second !== 0) {
            throw ValidationException::withMessages([
                'starts_at' => 'Appointment start time must match an available slot.',
            ]);
        }

        $workStart = $startsAt->setTime(self::WORK_START_HOUR, 0);
        $workEnd = $startsAt->setTime(self::WORK_END_HOUR, 0);

        if ($startsAt->lessThan($workStart) || $endsAt->greaterThan($workEnd)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Appointment must fit inside business hours.',
            ]);
        }
    }
}

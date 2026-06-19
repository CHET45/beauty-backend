<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Specialist;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    private const WORK_START_HOUR = 9;

    private const WORK_END_HOUR = 18;

    private const SLOT_STEP_MINUTES = 30;

    private const MIN_LEAD_TIME_MINUTES = 15;

    /**
     * Build the bookable slots for a date.
     *
     * When a specialist is given, availability is that specialist's own
     * calendar. Otherwise ("any") a slot is offered only while the relevant
     * pool still has free capacity, so a customer is never shown a time when
     * every eligible specialist is already busy.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableSlots(Service $service, CarbonInterface $date, ?Specialist $specialist = null): array
    {
        $workStart = CarbonImmutable::instance($date)->setTime(self::WORK_START_HOUR, 0);
        $workEnd = CarbonImmutable::instance($date)->setTime(self::WORK_END_HOUR, 0);

        $appointments = Appointment::query()
            ->notCancelled()
            ->where('starts_at', '<', $workEnd)
            ->where('ends_at', '>', $workStart)
            ->get(['specialist_id', 'starts_at', 'ends_at']);

        $poolIds = $specialist === null ? $this->relevantSpecialistIds($service) : collect();

        $earliestStart = CarbonImmutable::now()->addMinutes(self::MIN_LEAD_TIME_MINUTES);

        $slots = [];
        $slotStart = $workStart;

        while ($slotStart->addMinutes($service->duration_minutes)->lessThanOrEqualTo($workEnd)) {
            $slotEnd = $slotStart->addMinutes($service->duration_minutes);

            if ($slotStart->greaterThanOrEqualTo($earliestStart)) {
                $available = $specialist !== null
                    ? ! $this->specialistHasConflict($appointments, $specialist->id, $slotStart, $slotEnd)
                    : $this->freeCapacity($appointments, $poolIds, $slotStart, $slotEnd) > 0;

                $slots[] = [
                    'time' => $slotStart->format('H:i'),
                    'starts_at' => $slotStart->toIso8601String(),
                    'ends_at' => $slotEnd->toIso8601String(),
                    'available' => $available,
                ];
            }

            $slotStart = $slotStart->addMinutes(self::SLOT_STEP_MINUTES);
        }

        return $slots;
    }

    /**
     * @param  array{service_id:int,specialist_id?:int|null,customer_name:string,phone_country_code:string,customer_phone:string,customer_email?:string|null,starts_at:string,notes?:string|null}  $payload
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

            // Defense in depth: even if request validation is bypassed, a booking
            // can never reference a specialist who does not offer this service.
            $specialist = null;
            $specialistId = $payload['specialist_id'] ?? null;

            if ($specialistId !== null) {
                $specialist = $service->specialists()->active()->whereKey($specialistId)->first();

                if ($specialist === null) {
                    throw ValidationException::withMessages([
                        'specialist_id' => 'Selected specialist does not offer this service.',
                    ]);
                }
            }

            $startsAt = CarbonImmutable::parse($payload['starts_at'], config('app.timezone'));
            $endsAt = $startsAt->addMinutes($service->duration_minutes);

            $this->ensureBookableTime($startsAt, $endsAt);

            $dayAppointments = Appointment::query()
                ->notCancelled()
                ->where('starts_at', '>=', $startsAt->startOfDay())
                ->where('starts_at', '<', $startsAt->addDay()->startOfDay())
                ->lockForUpdate()
                ->get(['specialist_id', 'starts_at', 'ends_at']);

            if ($specialist !== null) {
                if ($this->specialistHasConflict($dayAppointments, $specialist->id, $startsAt, $endsAt)) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'This time slot is already booked.',
                    ]);
                }
            } else {
                $poolIds = $this->relevantSpecialistIds($service);

                if ($this->freeCapacity($dayAppointments, $poolIds, $startsAt, $endsAt) <= 0) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'No specialist is available at this time.',
                    ]);
                }
            }

            return Appointment::create([
                'service_id' => $service->id,
                // Null = "any": left for an admin to assign a specialist later.
                'specialist_id' => $specialist?->id,
                'customer_name' => $payload['customer_name'],
                'phone_country_code' => $payload['phone_country_code'],
                // Store digits only so lookups/filters compare reliably across drivers.
                'customer_phone' => preg_replace('/\D+/', '', $payload['customer_phone']),
                'customer_email' => $payload['customer_email'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Pending,
                'notes' => $payload['notes'] ?? null,
            ])->load(['service', 'specialist']);
        });
    }

    /**
     * Active specialists eligible to take this service: the service's own
     * specialists, or — if it has none assigned — every active specialist.
     * Falls back to an empty set only when no specialists exist at all.
     *
     * @return Collection<int, int>
     */
    private function relevantSpecialistIds(Service $service): Collection
    {
        $ids = $service->specialists()->active()->pluck('specialists.id');

        if ($ids->isNotEmpty()) {
            return $ids;
        }

        return Specialist::query()->active()->pluck('id');
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function specialistHasConflict(Collection $appointments, int $specialistId, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $this->overlapping($appointments, $start, $end)
            ->contains(fn (Appointment $a): bool => (int) $a->specialist_id === $specialistId);
    }

    /**
     * Remaining capacity of the pool at a slot. Booked specialists in the pool
     * and floating "any" (null) bookings each consume one slot of capacity. A
     * salon with no specialists configured keeps the legacy single-chair model.
     *
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, int>  $poolIds
     */
    private function freeCapacity(Collection $appointments, Collection $poolIds, CarbonInterface $start, CarbonInterface $end): int
    {
        $overlapping = $this->overlapping($appointments, $start, $end);

        $busyInPool = $overlapping
            ->whereNotNull('specialist_id')
            ->pluck('specialist_id')
            ->intersect($poolIds)
            ->unique()
            ->count();

        $floating = $overlapping->whereNull('specialist_id')->count();

        $capacity = $poolIds->isNotEmpty() ? $poolIds->count() : 1;

        return $capacity - $busyInPool - $floating;
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return Collection<int, Appointment>
     */
    private function overlapping(Collection $appointments, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $appointments->filter(
            fn (Appointment $a): bool => $a->starts_at->lessThan($end) && $a->ends_at->greaterThan($start)
        );
    }

    private function ensureBookableTime(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if ($startsAt->lessThan(CarbonImmutable::now()->addMinutes(self::MIN_LEAD_TIME_MINUTES))) {
            throw ValidationException::withMessages([
                'starts_at' => 'Appointments must be booked at least '.self::MIN_LEAD_TIME_MINUTES.' minutes in advance.',
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

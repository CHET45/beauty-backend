<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof AppointmentStatus
            ? $this->status->value
            : $this->status;

        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'customer_name' => $this->customer_name,
            'phone_country_code' => $this->phone_country_code,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $status,
            'notes' => $this->notes,
            'service' => new ServiceResource($this->whenLoaded('service')),
        ];
    }
}

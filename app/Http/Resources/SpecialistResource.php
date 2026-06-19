<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'photo_url' => $this->photo_url,
            'is_active' => (bool) $this->is_active,
            'service_ids' => $this->whenLoaded(
                'services',
                fn () => $this->services->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecialistResource;
use App\Models\Service;

class SpecialistController extends Controller
{
    // Active specialists who offer a given service — used by the booking screen
    // to let the customer pick a specialist (or "any").
    public function forService(Service $service)
    {
        $specialists = $service->specialists()
            ->active()
            ->orderBy('name')
            ->get();

        return SpecialistResource::collection($specialists);
    }
}

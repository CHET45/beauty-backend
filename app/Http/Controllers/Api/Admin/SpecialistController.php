<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSpecialistRequest;
use App\Http\Requests\Admin\UpdateSpecialistRequest;
use App\Http\Resources\SpecialistResource;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SpecialistController extends Controller
{
    public function index()
    {
        $specialists = Specialist::query()
            ->with('services')
            ->latest()
            ->get();

        return SpecialistResource::collection($specialists);
    }

    public function store(StoreSpecialistRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $specialist = Specialist::create($this->attributes($validated));
        $specialist->services()->sync($validated['service_ids'] ?? []);

        return response()->json([
            'message' => 'Specialist created.',
            'data' => new SpecialistResource($specialist->load('services')),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateSpecialistRequest $request, Specialist $specialist): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $specialist->update($this->attributes($validated));
        $specialist->services()->sync($validated['service_ids'] ?? []);

        return response()->json([
            'message' => 'Specialist updated.',
            'data' => new SpecialistResource($specialist->load('services')),
        ]);
    }

    public function destroy(Specialist $specialist): JsonResponse
    {
        // A specialist with bookings is kept for history; deactivate instead.
        if ($specialist->appointments()->exists()) {
            return response()->json([
                'message' => 'Specialists with bookings cannot be deleted. Deactivate them instead.',
            ], Response::HTTP_CONFLICT);
        }

        $specialist->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return collect($validated)
            ->only(['name', 'title', 'phone', 'bio', 'photo_url', 'is_active'])
            ->all();
    }
}

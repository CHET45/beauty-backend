<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->latest()
            ->get();

        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created.',
            'data' => new ServiceResource($service),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated.',
            'data' => new ServiceResource($service),
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        if ($service->appointments()->exists()) {
            return response()->json([
                'message' => 'Services with appointments cannot be deleted. Deactivate it instead.',
            ], Response::HTTP_CONFLICT);
        }

        $service->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->get();

        return response()->json([
            'data' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created.',
            'data' => $service,
        ], 201);
    }

    public function show(Service $service)
    {
        return response()->json([
            'data' => $service,
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated.',
            'data' => $service,
        ]);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'message' => 'Service deleted.',
        ]);
    }
}
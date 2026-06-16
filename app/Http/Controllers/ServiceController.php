<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->get();

        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Service::create($validated);

        return redirect()
            ->route('services.index')
            ->with('success', 'Услуга создана.');
    }

    public function edit(Service $service)
    {
        return view('services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return redirect()
            ->route('services.index')
            ->with('success', 'Услуга обновлена.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('success', 'Услуга удалена.');
    }
}
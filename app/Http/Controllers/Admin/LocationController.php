<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Models\Location;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    use ActivityLogTrait;

    public function index(): View
    {
        return view('admin.locations', [
            'locations' => Location::withCount('stalls')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->fragment('location-records'),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $location = Location::create($request->validated());
        $this->logActivity('created_location', $request->user()->name.' created location "'.$location->name.'"', $location);

        return back()->with('success', 'Location created successfully.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        if ($location->stalls()->exists()) {
            return back()->with('error', 'Cannot delete a location that has stalls assigned to it.');
        }

        $this->logActivity('deleted_location', $request->user()->name.' deleted location "'.$location->name.'"', null, ['deleted_location_name' => $location->name]);
        $location->delete();

        return back()->with('success', 'Location deleted successfully.');
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LocationController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:locations');
    }


    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New Country'),
                'url' => '#',
                'target' => '#addNewLocationDrawer',
            ],
        ];

        $countries = Location::withCount('childrenLocation')->whereNull('location_id')->paginate();
        return Inertia::render('Admin/Location/Index', compact('countries', 'segments', 'buttons'));
    }

    public function store(Request $request)
    {
      
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:locations,name'],
        ]);

        Location::create(['name' => $request->get('name'), 'slug' => Str::slug($request->get('name'))]);

        return back();
    }

    public function show(Location $location)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New State'),
                'url' => '#',
                'target' => '#addNewStateDrawer',
            ],
        ];
        $country = $location;
        $states = $country->childrenLocation()->paginate();

        return Inertia::render('Admin/Location/States/Index', compact('country', 'states', 'segments', 'buttons'));
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:locations,name,' . $location->id],
        ]);

        $location->update([
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name')),
        ]);

        return back();
    }

    public function destroy(Location $location)
    {
        $location->childrenLocation()->delete();
        $location->delete();
        return back();
    }
}

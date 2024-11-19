<?php

namespace App\Http\Controllers\Admin;

use App\Models\Location;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:locations');
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => ['required'],
            'name' => ['required', 'string', 'max:255', 'unique:locations,name']
        ]);

        Location::create([
            'location_id' => $request->get('country_id'),
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name'))
        ]);

        return back();
    }

    public function update(Request $request, Location $state)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255']
        ]);

        $state->update([
            'name' => $request->get('name'),
            'slug' => Str::slug($request->get('name'))
        ]);

        return back();
    }

    public function destroy(Location $state)
    {
        $state->delete();
        return back();
    }
}

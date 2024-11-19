<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->country_id ?? $request->location_id ?? null;
        return Location::where('location_id', $id)->get();
    }
}

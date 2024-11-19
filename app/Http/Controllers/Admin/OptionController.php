<?php

namespace App\Http\Controllers\Admin;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class OptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page-settings');
    }


    public function update(Request $request, $key)
    {
        $option = Option::where('key', $key)->first();

        if (empty($option)) {
            $option = new Option;
            $option->key = $key;
        }
        
        if ($key == 'tax') {
            $option->value = $request->tax;
        } else {
            $option->value = is_array($request->all()) ? json_encode($request->all()) : $request->all();
        }

        $option->save();

        Cache::forget($option->key);

        return back();
    }
}

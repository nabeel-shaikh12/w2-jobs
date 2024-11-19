<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:theme-setting');
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [];
        $theme_path = get_option('theme_path');
        return Inertia::render('Admin/Theme/Index', [
            'theme_path' => $theme_path,
            'segments' => $segments,
            'buttons' => $buttons,
        ]);
    }
}

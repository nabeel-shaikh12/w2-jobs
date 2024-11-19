<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Inertia\Inertia;
class CronjobController extends Controller
{
    public function __construct()
    {
         $this->middleware('permission:cron-job'); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $segments = request()->segments();
       
        $buttons = [];

        $jobs=[
            [
                'title' => __('Execute Schedule'),
                'url' => '* * * * * ' . base_path() . '/artisan schedule:run',
                'time' => __('for execute the queue jobs')
            ],
            [
                'title'=> __('Notify to customer before expire the subscription'),
                'url' => 'curl -s '.url('/cron/notify-to-user'),
                'time' => __('Everyday')
            ]
        ];

        return Inertia::render('Admin/Cron/Index',compact('segments','buttons','jobs'));
    }

    
}

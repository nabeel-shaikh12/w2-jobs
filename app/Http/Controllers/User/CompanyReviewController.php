<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CompanyReview;
use App\Models\Notification;
use Illuminate\Http\Request;

class CompanyReviewController extends Controller
{
   

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ratting' => ['required', 'digits_between:1,5'],
            'comment' => ['required', 'string', 'max:200'],
        ]);

        CompanyReview::query()->firstOrCreate([
            'user_id' => auth()->id(),
            'company_id' => $request->job['user_id'],
            'job_id' => $request->job['id']
        ], [
            'ratting' => $request->ratting,
            'comment' => $request->comment,
        ]);

        Notification::create([
            'user_id' => $request->job['user_id'],
            'title' => 'A new rating has been received',
            'comment' => __('Review given by :name', ['name' => auth()->user()->name]),
            'url' => '#',
        ]);

        return back();
    }

   
}

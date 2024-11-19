<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\CandidateReview;
use App\Models\CompanyReview;
use App\Models\Notification;
use App\Models\Opening;
use Illuminate\Http\Request;
use Auth;
class CandidateReviewController extends Controller
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

        $job = Opening::where('user_id',Auth::id())->findorFail($request->job['id']);
        
        CandidateReview::query()->updateOrCreate([
            'company_id' => auth()->id(),
            'user_id' => $request->candidate_id,
            'job_id' => $request->job['id'],
        ], [
            'ratting' => $request->ratting,
            'comment' => $request->comment,
        ]);

        Notification::create([
            'user_id' => $request->candidate_id,
            'title' => 'A new rating has been received',
            'comment' => __('Review given by :name', ['name' => auth()->user()->name]),
            'url' => '#',
        ]);

        return back();
    }

    
}

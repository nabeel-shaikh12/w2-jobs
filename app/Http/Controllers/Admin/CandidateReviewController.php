<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\CandidateReview;
use App\Http\Controllers\Controller;

class CandidateReviewController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:candidate-reviews');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $reviews = CandidateReview::latest()
            ->with('candidate', 'company', 'author', 'job')
            ->when(request()->filled('search'), function ($query) {
                if (request('type') == 'candidate_email') {
                    return $query->whereHas('candidate', function ($q) {
                        return $q->where('email', request('search'));
                    });
                }

                if (request('type') == 'company_email') {
                    return $query->whereHas('company', function ($q) {
                        return $q->where('email', request('search'));
                    });
                }
            })
            ->paginate();
        $segments = request()->segments();
        $buttons = [];

        $total = CandidateReview::count();
        $averageRatings = CandidateReview::avg('ratting');
        $todaysReviews = CandidateReview::whereDate('created_at', today())->count();

        return Inertia::render('Admin/CandidateReviews/Index', [
            'reviews' => $reviews,
            'request' => request()->all(),
            'segments' => $segments,
            'buttons' => $buttons,
            'total' => $total,
            'averageRatings' => round($averageRatings, 2) ?? 0,
            'todaysReviews' => $todaysReviews
        ]);
    }





    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CandidateReview $candidateReview)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-list"></i>' . __(' Back to list'),
                'url' => route('admin.candidate-reviews.index')
            ]
        ];

        return Inertia::render('Admin/CandidateReviews/Edit', compact('candidateReview', 'segments', 'buttons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CandidateReview $candidateReview)
    {
        $request->validate([
            'ratting' => ['required', 'numeric', 'min:0', 'max:5'],
            'comment' => ['required', 'string', 'max:200']
        ]);

        $candidateReview->update($request->only(['ratting', 'comment']));

        return back()->with('success', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CandidateReview $candidateReview)
    {
        $candidateReview->delete();
        return back()->with('success', 'Deleted successfully');
    }
}

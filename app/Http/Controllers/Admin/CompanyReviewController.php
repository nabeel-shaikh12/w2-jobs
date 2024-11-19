<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\CompanyReview;
use App\Http\Controllers\Controller;

class CompanyReviewController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:company-reviews');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $reviews = CompanyReview::latest()
            ->with('company', 'author', 'job')
            ->when(request()->filled('search'), function ($query) {
                if (request('type') == 'company_email') {
                    return $query->whereHas('company', function ($q) {
                        return $q->where('email', request('search'));
                    });
                } else {
                    return $query->whereHas('author', function ($q) {
                        return $q->where('email', request('search'));
                    });
                }
            })
            ->paginate();
        $segments = request()->segments();
        $buttons = [];

        $total = CompanyReview::count();
        $averageRatings = CompanyReview::avg('ratting');
        $todaysReviews = CompanyReview::whereDate('created_at', today())->count();

        return Inertia::render('Admin/CompanyReviews/Index', [
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
    public function edit(CompanyReview $companyReview)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-list"></i>' . __(' Back to list'),
                'url' => route('admin.company-reviews.index')
            ]
        ];

        return Inertia::render('Admin/CompanyReviews/Edit', compact('companyReview', 'segments', 'buttons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompanyReview $companyReview)
    {
        $request->validate([
            'ratting' => ['required', 'numeric', 'min:0', 'max:5'],
            'comment' => ['required', 'string', 'max:200']
        ]);

        $companyReview->update($request->only(['ratting', 'comment']));

        return back()->with('success', 'Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyReview $companyReview)
    {
        $companyReview->delete();
        return back()->with('success', 'Deleted successfully');
    }
}

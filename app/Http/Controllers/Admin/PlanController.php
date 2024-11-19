<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use Auth;
use Session;
use Inertia\Inertia;

class PlanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:subscriptions');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $plans = Plan::latest()->withCount('activeuser')->latest()->get();
        $segments = request()->segments();

        $buttons = [
            [
                'name' => '<i class="bx bx-plus"></i>  &nbsp' . __('Create Plan'),
                'url' => '#',
                'target' => '#addNewPlanDrawer',
            ],

        ];


        return Inertia::render('Admin/Plan/Index', compact('plans', 'segments', 'buttons'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $segments = request()->segments();

        $buttons = [
            [
                'name' => __('Plans'),
                'url' => '/admin/plan'
            ],

        ];

        return Inertia::render('Admin/Plan/Create', compact('segments', 'buttons'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:100',
            'price' => 'required',
            'days'  => 'required',
            'plan_data*' => 'required',
        ]);

        if (isset($request->is_tria)) {
            $request->validate([
                'trial_days' => 'required',
            ]);
        }

        $plan                 = new Plan;
        $plan->title          = $request->title;
        $plan->price          = $request->price;
        $plan->is_featured    = $request->is_featured == true ? 1 : 0;
        $plan->is_recommended = $request->is_recommended == true ? 1 : 0;
        $plan->is_trial       = $request->is_trial == true ? 1 : 0;
        $plan->status         = $request->status == true ? 1 : 0;
        $plan->days           = $request->days ?? 0;
        $plan->trial_days     = $request->trial_days ?? 0;
        $plan->data           = $request->plan_data  ?? [];
        $plan->save();

        return back();
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Plan $plan)
    {
        $segments = request()->segments();

        $buttons = [
            [
                'name' => __('Back'),
                'url' => '/admin/plan'
            ],

        ];

        return Inertia::render('Admin/Plan/Edit', compact('segments', 'buttons', 'plan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:100',
            'price' => 'required',
            'days'  => 'required',
            'plan_data*' => 'required',
        ]);



        $plan                 = Plan::findorFail($id);
        $plan->title          = $request->title;
        $plan->price          = $request->price;

        $plan->is_featured    = $request->is_featured == true ? 1 : 0;
        $plan->is_recommended = $request->is_recommended == true ? 1 : 0;
        $plan->is_trial       = $request->is_trial == true ? 1 : 0;
        $plan->status         = $request->status == true ? 1 : 0;
        $plan->days           = $request->days ?? 0;
        $plan->trial_days     = $request->trial_days ?? 0;
        $plan->data           = $request->plan_data ?? [];
        $plan->save();

        return to_route('admin.plan.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $plan = Plan::withCount('activeuser')->findorFail($id);
        if ($plan->activeuser_count != 0) {
            return response()->json([
                'message' =>  __('You cant delete this plan because this plan already useing some users'),
            ], 403);
        }
        $plan->delete();

        return back();
    }
}

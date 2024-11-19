<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DescriptionTemplate;
use App\Models\Location;
use App\Models\User;
use App\Models\Userlocation;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CompanyInfoController extends Controller
{
    use Uploader;
    public function index()
    {
        $shortcodes = array_map(fn ($el) => preg_replace('/\[|\]/', '', $el), DescriptionTemplate::SHORTCODES);
        $services = Category::query()->where('type', 'service')->get();
        $countries = Location::query()->whereNull('location_id')->get();
        return Inertia::render('Employer/CompanyInfo', [
            'services' => $services,
            'countries' => $countries,
            'shortcodes' => $shortcodes,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->step == 1) {
            $request->validate([
                'company_name' => 'required|string',
                'company_size' => 'required|string',
                'year_of_establishment' => 'required|string|before:today',
                'company_address' => 'required|string',
                'company_intro' => 'nullable|string',
                'company_teams' => 'required|array|min:3',
                'company_teams.*' => 'image|mimes:jpg,png,webp,jpeg|max:2048|',
                'country_id' => 'required|exists:locations,id',
                'state_id' => 'required|exists:locations,id',
                'service_id' => 'required|exists:categories,id',
                'category_id' => 'required|array',
            ]);
            return;
        }

        $request->validate([
            'business.description' => 'required|string',
            'business.license_no' => 'nullable|string',
            'business.rl_no' => 'nullable|numeric',
            'business.site_url' => 'nullable|string',
            'contact.name' => 'required|string',
            'contact.designation' => 'required|string',
            'contact.email' => 'required|email',
            'contact.mobile' => 'nullable|string',
            'social.linkedin' => 'nullable|string',
            'social.twitter' => 'nullable|string',
            'social.facebook' => 'nullable|string',
            'social.instagram' => 'nullable|string',
        ]);
        $user = User::findOrFail(auth()->id());
        if ($request->hasFile('company_teams')) {
            $teams = $this->multipleSaveFile($request, 'company_teams');
        }
        $company =  [
            'name' => $request->company_name,
            'size' => $request->company_size,
            'year_of_establishment' => $request->year_of_establishment,
            'address' => $request->company_address,
            'intro' => $request->company_intro,
            'teams' => $teams,

        ];
        $user->category_id = $request->service_id;
        DB::beginTransaction();
        try {
            $user->meta = [
                'company' => $company, 'contact' => $request->contact,
                'business' => $request->business, 'social' => $request->social
            ];
            $user->save();
            $user->categories()->sync($request->category_id);
            Userlocation::create([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
            ]);
            DB::commit();
            return back();
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}

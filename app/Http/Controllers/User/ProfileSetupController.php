<?php

namespace App\Http\Controllers\User;

use Inertia\Inertia;
use App\Models\Category;
use App\Models\Location;
use App\Traits\Uploader;
use App\Models\ExpertLevel;
use App\Models\Userlocation;
use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\UserEducationQualification;
use App\Http\Requests\StoreProfileSetupRequest;

class ProfileSetupController extends Controller
{
    use Uploader;
    public function index()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        if ($user->completedProfileSetup()) {
            return redirect(route('user.dashboard'));
        }

        $meta = $user->meta ?? [];
        $countries = Location::whereNull('location_id')->get();
        $services = Category::whereType('service')->active()->get(['id', 'title', 'type', 'status']);
        $education_qualifications = Qualification::all();
        $expertLevels = ExpertLevel::all();

        $meta['education_qualifications'] = $user->educationQualifications()->get()->pluck('meta');

        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
        ->values()->toArray();

        return Inertia::render(
            'User/ProfileSetup',
            [
                'meta' => $meta,
                'user' => $user,
                'countries' => $countries,
                'services' => $services,
                'education_qualifications' => $education_qualifications,
                'expertLevels' => $expertLevels,
                'currencies' => $currencies
            ]
        );
    }

    public function store(StoreProfileSetupRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];
        $is_editing = $request->boolean('is_editing');

        $step = $request->get('step');

        if ($step == 1) {

            $meta['overview'] = $request->overview;
            $meta['gender'] = $request->gender;
            $meta['date_of_birth'] = $request->date_of_birth;

            $user->update([
                'meta' => $meta,
                'avatar' => $request->hasFile('avatar') ? $this->saveFile($request, 'avatar') : $user->avatar,
                'phone' => $request->phone,
            ]);
        }

        if ($step == 2) {

            DB::transaction(function () use ($user, $is_editing, $request, &$meta) {
               
                Userlocation::query()->where('user_id', $user->id)->delete();
              


                Userlocation::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                ]);

                $meta['country_id'] = $request->country_id;
                $meta['state_id'] = $request->state_id;
                $meta['address'] = $request->address;
                $user->address = $request->address;
            });
        }
        if ($step == 3) {

            $meta['expert_level'] = $request->expert_level;
            $meta['expected_salary'] = $request->expected_salary;
            $meta['currency'] = $request->currency;
            $meta['work_experiences'] = $request->work_experiences;

            $meta['service_id'] = $request->service_id;
            $meta['category_id'] = $request->category_id;
            $meta['skills'] = (array) $request->skills;

            DB::transaction(function () use ($request, $user, $meta) {
                $user->categories()->sync([$request->category_id]);
                $user->tags()->sync((array) $request->skills);
                $user->update(['meta' => $meta, 'category_id' => $request->service_id]);
            });
        }
        if ($step == 4) {

            DB::transaction(function () use ($is_editing, $request, $user) {
               
                UserEducationQualification::query()->where('user_id', $user->id)->delete();
                

                collect($request['education_qualifications'])->each(function ($item) {
                    UserEducationQualification::create([
                        'qualification_id' => $item['degree'],
                        'user_id' => auth()->id(),
                        'meta' => [
                            ...$item
                        ],
                    ]);
                });
            });
        }
        if ($step == 5) {

            if ($request->hasFile('resume')) {
                $meta['resume'] = $this->saveFile($request, 'resume');
                $user->update(['meta' => $meta]);
            }
        }

        $meta['step_completed'] = $step;
        $user->meta = $meta;
        $user->save();

        if ($step >= 5) {
            return Inertia::location(route('user.dashboard'));
        }

        return back();
    }
}

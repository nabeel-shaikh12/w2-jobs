<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileSetupRequest2;
use App\Models\Category;
use App\Models\ExpertLevel;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\UserEducationQualification;
use App\Models\Userlocation;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResumeController extends Controller
{
    use Uploader;
    public function index()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];

        $countries = Location::whereNull('location_id')->get();
        $services = Category::whereType('service')->active()->get(['id', 'title', 'type', 'status']);
        $education_qualifications = Qualification::all();
        $expertLevels = ExpertLevel::all();
        $portfolios = $user->portfolios;

        $meta['education_qualifications'] = $user->educationQualifications()->get()->pluck('meta');
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
        ->values()->toArray();
        return Inertia::render('User/Resume', [
            'meta' => $meta,
            'countries' => $countries,
            'services' => $services,
            'education_qualifications' => $education_qualifications,
            'expertLevels' => $expertLevels,
            'portfolios' => $portfolios,
            'candidate' => $user,
            'currencies' => $currencies
        ]);
    }

    public function store(StoreProfileSetupRequest2 $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        if ($request->submit_type == 'video_intro') {
            return $this->uploadVideoIntro($request);
        }

        if ($request->submit_type == 'resume') {
            $user->update([
                'meta' => [
                    ...$user->meta,
                    'resume' => $this->saveFile($request, 'resume'),
                ],
            ]);
            return back();
        }

        $meta = $user->meta;

        $meta['overview'] = $request->overview;
        $meta['gender'] = $request->gender;
        $meta['date_of_birth'] = $request->date_of_birth;

        $meta['expert_level'] = $request->expert_level;
        $meta['expected_salary'] = $request->expected_salary;
        $meta['currency'] = $request->currency;
        $meta['work_experiences'] = $request->work_experiences;

        // just preserve for better editing
        $meta['country_id'] = $request->country_id;
        $meta['state_id'] = $request->state_id;
        $meta['address'] = $request->address;

        $meta['service_id'] = $request->service_id;
        $meta['category_id'] = $request->category_id;
        $meta['skills'] = $request->skills;

        $meta['education_qualifications'] = $request->education_qualifications;
        // just preserve end

        $meta['avatar'] = $request->hasFile('avatar') ? $this->saveFile($request, 'avatar') : $user->avatar;

        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->category_id = $request->service_id;
        $user->meta = $meta;

        DB::transaction(function () use ($user, $request) {

            Userlocation::query()->where('user_id', $user->id)->delete();
            Userlocation::create([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
            ]);

            $user->categories()->sync([$request->category_id]);

            $user->tags()->sync((array) $request->skills);

            UserEducationQualification::query()->where('user_id', $user->id)->delete();
            foreach ($request['education_qualifications'] ?? [] as $item) {
                UserEducationQualification::create([
                    'qualification_id' => $item['degree'],
                    'user_id' => auth()->id(),
                    'meta' => [
                        ...$item,
                    ],
                ]);
            };

            $user->save();
        });

        return back();
    }

    private function uploadVideoIntro(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];
        $meta['video_intro'] = $request->video_intro;

        $meta['video_id'] = null;
        if (isset($user->meta, $request->video_intro)) {
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $request->video_intro, $match);
            $meta['video_id'] = $match[1];
        }

        $user->update(['meta' => $meta]);
        return back();
    }

    public function destroy()
    {

        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];
        $submit_type = request('submit_type');

        if ($submit_type == 'video_intro') {
            $meta['video_intro'] = null;
            $meta['video_id'] = null;
        } elseif ($submit_type == 'resume') {
            if ($meta && isset($meta['resume'])) {
                $this->removeFile($meta['resume']);
            }

            $meta['resume'] = null;
        }

        $user->update(['meta' => $meta]);

        return back();
    }
}

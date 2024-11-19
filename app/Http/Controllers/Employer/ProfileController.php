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
use Inertia\Inertia;

class ProfileController extends Controller
{
    use Uploader;
    public function index()
    {
        $user = User::query()->select('name', 'id', 'username', 'meta', 'avatar', 'created_at', 'category_id')
            ->with(['countries', 'states', 'categories'])
            ->findOrFail(auth()->id());

        $service = Category::query()
            ->where('type', 'service')
            ->get();
        $categories = Category::query()
            ->where('type', 'job_category')
            ->where('category_id', $user->category_id)
            ->get();
        $countries = Location::query()->whereNull('location_id')->get();
        $states = Location::query()->whereNotNull('location_id')
            ->where('location_id', $user->countries[0]?->id)->get();
        $shortcodes = array_map(function ($el) {
            return  preg_replace('/\[|\]/', '', $el);
        }, DescriptionTemplate::SHORTCODES);

        return Inertia::render('Employer/Profile', [
            'user' => $user,
            'service' => $service,
            'countries' => $countries,
            'states' => $states,
            'categories' => $categories,
            'shortcodes' => $shortcodes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail(auth()->id());
        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10048',
            ]);
            $avatar = $this->saveFile($request, 'avatar');
            $user->avatar = $avatar;
            $user->save();
            return inertia()->location(route('employer.profile.index'));
        }
        $request->validate([
            'meta.company.name' => 'required|string',
            'meta.company.size' => 'required|string',
            'meta.company.year_of_establishment' => 'required|string',
            'meta.company.address' => 'required|string',
           
            'country_id' => 'required|exists:locations,id',
            'state_id' => 'required|exists:locations,id',
            'category_id' => 'required|exists:categories,id',
            'tag_id' => 'required|array',
            'meta.business.description' => 'required|string',
            'meta.business.license_no' => 'nullable|string',
            'meta.business.rl_no' => 'nullable|numeric',
            'meta.business.site_url' => 'nullable|string',
            'meta.contact.name' => 'required|string',
            'meta.contact.designation' => 'required|string',
            'meta.contact.email' => 'required|email',
            'meta.contact.mobile' => 'nullable|string',
            'meta.social.linkedin' => 'nullable|string',
            'meta.social.twitter' => 'nullable|string',
            'meta.social.facebook' => 'nullable|string',
            'meta.social.instagram' => 'nullable|string',
        ]);

        $user->category_id = $request->category_id;
        $video_id = data_get($user->meta, 'company.video_id');
        $video_intro = data_get($user->meta, 'company.video_intro');
       

        $requestMeta = $request->meta;

        $requestMeta['company']['video_id'] = $video_id;
        $requestMeta['company']['video_intro'] = $video_intro;

        $user->meta = $requestMeta;
        $user->save();
        $user->categories()->sync($request->tag_id);
        $location = Userlocation::where('user_id', $user->id)->firstOrFail();
        $location->update([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
        ]);
        return back();
    }

    public function storeVideoIntro(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];
        $meta['company']['video_intro'] = $request->video_intro;

        $video_id = null;
        if (isset($meta, $meta['company']['video_intro'])) {
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $meta['company']['video_intro'], $match);
            $video_id = $match[1];
        }

        $meta['company']['video_id'] = $video_id;

       
        $user->update(['meta' => $meta]);

        return back();
    }

    public function destroyVideoIntro()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $meta = $user->meta ?? [];
        $meta['company']['video_intro'] = null;
        $meta['company']['video_id'] = null;
        $user->update(['meta' => $meta]);
        return back();
    }
}

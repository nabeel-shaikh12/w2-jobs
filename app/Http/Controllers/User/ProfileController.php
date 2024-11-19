<?php

namespace App\Http\Controllers\User;

use Inertia\Inertia;
use App\Models\Location;
use App\Traits\Uploader;
use App\Models\Userlocation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    use Uploader;
    public function edit()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $user->load(['countries', 'states']);


        return Inertia::render('User/Profile', [
            'user' => $user,
           
        ]);
    }

    public function update(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        if ($request->hasFile('avatar')) {
            return $this->updateAvatar($request);
        }

        $request->validate([

            'country_id' => 'required|exists:locations,id',
            'state_id' => 'required|exists:locations,id',

            'meta.social.linkedin' => 'nullable|string',
            'meta.social.twitter' => 'nullable|string',
            'meta.social.facebook' => 'nullable|string',
            'meta.social.instagram' => 'nullable|string',
        ]);

        $user->update([
            'name' => $request->name,
            'meta' => $request->meta
        ]);

        $location = Userlocation::where('user_id', $user->id)->firstOrFail();

        $location->update([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
        ]);

        return back();
    }

    private function updateAvatar(Request $request)
    {

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ]);

        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        $this->removeFile($user->avatar);

        $user->update([
            'avatar' => $this->saveFile($request, 'avatar')
        ]);

        return inertia()->location(route('user.profile.edit'));
    }
}

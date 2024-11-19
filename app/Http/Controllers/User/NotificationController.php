<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())->latest()->paginate(30);

        return view('user.notifications.index', compact('notifications'));
    }



    public function update($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findorFail($id);
        $notification->seen = 1;
        $notification->save();
        return redirect()->back();
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       
        $notification = Notification::where('user_id', Auth::id())->findorFail($id);
        $notification->seen = 1;
        $notification->save();

        return redirect($notification->url);
    }
}

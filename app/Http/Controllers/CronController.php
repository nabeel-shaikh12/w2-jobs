<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedulemessage;
use Carbon\Carbon;
use App\Models\Schedulecontact;
use App\Models\User;
use App\Models\Device;
use App\Traits\Notifications;
class CronController extends Controller
{
    use Notifications;


    /**
     * notify to subscribers before expire the subscription
     *
     * @return \Illuminate\Http\Response
     */
    public function notifyToUser()
    {
       $willExpire = today()->addDays(7)->format('Y-m-d');
       $users = User::whereHas('subscription')->with('subscription')->where('will_expire',$willExpire)->latest()->get();

       foreach ($users as $key => $user) {
           $this->sentWillExpireEmail($user);
       }

       return "Cron job executed";
    }

  
}

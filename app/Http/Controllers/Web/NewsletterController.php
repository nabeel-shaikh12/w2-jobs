<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Newsletter\Facades\Newsletter;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        Newsletter::subscribe($request->email);

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Services\SeoMeta;
use App\Traits\Seo;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class ContactController extends Controller
{
    use Seo;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $locale = current_locale();
        $contact = get_option('contact-page', true);
        $recaptchaSiteKey = env('GOOGLE_RECAPTCHA_KEY');
        return Inertia::render('Web/Contact', [
            'contact' => $contact,
            'seo' =>  SeoMeta::init('seo_contact'),
            'recaptchaSiteKey' => $recaptchaSiteKey,
        ]);
    }

    /**
     * Send email to the admin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendMail(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'email' => 'required|email:rfc,dns|max:40',
            'subject' => 'required|max:100',
            'message' => 'required|max:500',
        ]);

        try {
            $data['name'] = $request->name;
            $data['email'] = $request->email;
            $data['phone'] = $request->phone;
            $data['subject'] = $request->subject;
            $data['message'] = $request->message;

            env('QUEUE_MAIL') == true ? Mail::to(env('MAIL_TO'))->queue(new ContactMail($data)) : Mail::to(env('MAIL_TO'))->send(new ContactMail($data));

            return back();
        } catch (Exception $e) {
            Session::flash('error', __('Something wrong'));

            return back();
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Traits\Dotenv;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\FacadeSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class UpdateController extends Controller
{
    use Dotenv;

    public function __construct()
    {
        $this->middleware('permission:developer-settings');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Session::forget('flash');
        $updateData = Session::get('update-data');
        

        $segments = request()->segments();
        $buttons = [];
        $appVersion = env('APP_VERSION');
        $purchaseKey = env('SITE_KEY');
        
        return Inertia::render('Admin/Update/Index', [
            'version' => $appVersion,
            'purchaseKey' => $purchaseKey,
            'segments' => $segments,
            'buttons' => $buttons,
            'updateData' => $updateData
        ]);
    }



    /**
     * check new update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ( env('SITE_KEY') == null) {
            Session::flash('flash', 'item purchase is required');
            return back();
        }
        $body['purchase_key'] = env('SITE_KEY');
        $body['url'] = url('/');
        $body['current_version'] = env('APP_VERSION', 1);

        $response =  Http::post('https://api-saas-div.lpress.xyz/api/check-update', $body);

        $body = json_decode($response->body());

        if ($response->status() != 200) {
            Session::flash('flash', $body->message);
            return back();
        }

        Session::put('update-data', [
            'message' => $body->message,
            'version' => $body->version
        ]);
        return back();
    }

    public function update($version)
    {
       
        $site_key = env('SITE_KEY');
        $body['purchase_key'] = $site_key;
        $body['url'] = url('/');
        $body['version'] = $version;

        $response = Http::post('https://api-saas-div.lpress.xyz/api/pull-update', $body);
        $response = json_decode($response->body());

        foreach ($response->updates ?? [] as $key => $row) {
            if ($row->type == 'file') {
                $fileData = Http::get($row->file);
                $fileData = $fileData->body();

                File::put(base_path($row->path), $fileData);
            } elseif ($row->type == 'folder') {
                $path = $row->path . '/' . $row->name;

                if (!File::exists(base_path($path))) {
                    File::makeDirectory(base_path($path), 0777, true, true);
                }
            } elseif ($row->type == 'command') {
                Artisan::call($row->command);
            }
        }

        $this->editEnv('APP_VERSION', $version);

        Session::forget('update-data');
        Session::flash('success', 'Successfully updated to ' . $version);

        return back();
    }
}

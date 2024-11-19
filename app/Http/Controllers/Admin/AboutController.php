<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Option;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AboutController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:about');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Cache::flush();

        $languages = get_option('languages', true);
        $about =  get_option('about-page', true, true);
        $current_lang = app()->getLocale();

        $segments = request()->segments();
        $buttons = [];

        return  Inertia::render('Admin/About/Index', compact('languages', 'segments', 'buttons', 'about', 'current_lang'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        $locale = current_locale() ?? 'en';
        $key = 'about-page';
        $option = Option::where('key', $key)->where('lang', $locale)->first();

        $optionData = $request->about;

        if (empty($option)) {

            $option = new Option;
            $option->key = 'about-page';
            $option->lang = $locale;

            // ----------------Call to action 1---------------------//
            if ($request->hasFile('about.cta_s1.image')) {
                $optionData['cta_s1']['image'] = $this->saveFile($request, 'about.cta_s1.image');
            }
            if ($request->hasFile('about.cta_s1.image1')) {
                $optionData['cta_s1']['image1'] = $this->saveFile($request, 'about.cta_s1.image1');
            }
            if ($request->hasFile('about.cta_s1.image2')) {
                $optionData['cta_s1']['image2'] = $this->saveFile($request, 'about.cta_s1.image2');
            }
            if ($request->hasFile('about.cta_s1.image3')) {
                $optionData['cta_s1']['image3'] = $this->saveFile($request, 'about.cta_s1.image3');
            }
            if ($request->hasFile('about.cta_s1.image4')) {
                $optionData['cta_s1']['image4'] = $this->saveFile($request, 'about.cta_s1.image4');
            }
            if ($request->hasFile('about.cta_s1.image5')) {
                $optionData['cta_s1']['image5'] = $this->saveFile($request, 'about.cta_s1.image5');
            }
            if ($request->hasFile('about.cta_s1.image6')) {
                $optionData['cta_s1']['image6'] = $this->saveFile($request, 'about.cta_s1.image6');
            }

            // ----------------Call to action 2---------------------//
            if ($request->hasFile('about.cta_s2.image')) {
                $optionData['cta_s2']['image'] = $this->saveFile($request, 'about.cta_s2.image');
            }
            if ($request->hasFile('about.cta_s2.image1')) {
                $optionData['cta_s2']['image1'] = $this->saveFile($request, 'about.cta_s2.image1');
            }
            if ($request->hasFile('about.cta_s2.image2')) {
                $optionData['cta_s2']['image2'] = $this->saveFile($request, 'about.cta_s2.image2');
            }
            if ($request->hasFile('about.cta_s2.image3')) {
                $optionData['cta_s2']['image3'] = $this->saveFile($request, 'about.cta_s2.image3');
            }
            if ($request->hasFile('about.cta_s2.image4')) {
                $optionData['cta_s2']['image4'] = $this->saveFile($request, 'about.cta_s2.image4');
            }
            if ($request->hasFile('about.cta_s2.image5')) {
                $optionData['cta_s2']['image5'] = $this->saveFile($request, 'about.cta_s2.image5');
            }
            if ($request->hasFile('about.cta_s2.image6')) {
                $optionData['cta_s2']['image6'] = $this->saveFile($request, 'about.cta_s2.image6');
            }


            // video section
            if ($request->hasFile('about.video_s1.image')) {
                $optionData['video_s1']['image'] = $this->saveFile($request, 'about.video_s1.image');
            }

            $data = json_encode($optionData);
            $option->value = $data;
            $option->save();
        } else {

            $oldData = json_decode($option->value ?? '');

            // call to action  1
            if ($request->hasFile('about.cta_s1.image')) {
                $optionData['cta_s1']['image'] = $this->saveFile($request, 'about.cta_s1.image');
            } else {
                $optionData['cta_s1']['image'] = $oldData->cta_s1?->image ?? null;
            }
            if ($request->hasFile('about.cta_s1.image1')) {
                $optionData['cta_s1']['image1'] = $this->saveFile($request, 'about.cta_s1.image1');
            } else {
                $optionData['cta_s1']['image1'] = $oldData->cta_s1?->image1 ?? null;
            }

            if ($request->hasFile('about.cta_s1.image2')) {
                $optionData['cta_s1']['image2'] = $this->saveFile($request, 'about.cta_s1.image2');
            } else {
                $optionData['cta_s1']['image2'] = $oldData->cta_s1?->image2 ?? null;
            }

            if ($request->hasFile('about.cta_s1.image3')) {
                $optionData['cta_s1']['image3'] = $this->saveFile($request, 'about.cta_s1.image3');
            } else {
                $optionData['cta_s1']['image3'] = $oldData->cta_s1?->image3 ?? null;
            }

            if ($request->hasFile('about.cta_s1.image4')) {
                $optionData['cta_s1']['image4'] = $this->saveFile($request, 'about.cta_s1.image4');
            } else {
                $optionData['cta_s1']['image4'] = $oldData->cta_s1?->image4 ?? null;
            }

            if ($request->hasFile('about.cta_s1.image5')) {
                $optionData['cta_s1']['image5'] = $this->saveFile($request, 'about.cta_s1.image5');
            } else {
                $optionData['cta_s1']['image5'] = $oldData->cta_s1?->image5 ?? null;
            }

            if ($request->hasFile('about.cta_s1.image6')) {
                $optionData['cta_s1']['image6'] = $this->saveFile($request, 'about.cta_s1.image6');
            } else {
                $optionData['cta_s1']['image6'] = $oldData->cta_s1?->image6 ?? null;
            }


            // call to action  2
            if ($request->hasFile('about.cta_s2.image')) {
                $optionData['cta_s2']['image'] = $this->saveFile($request, 'about.cta_s2.image');
            } else {
                $optionData['cta_s2']['image'] = $oldData->cta_s2?->image ?? null;
            }
            if ($request->hasFile('about.cta_s2.image1')) {
                $optionData['cta_s2']['image1'] = $this->saveFile($request, 'about.cta_s2.image1');
            } else {
                $optionData['cta_s2']['image1'] = $oldData->cta_s2?->image1 ?? null;
            }

            if ($request->hasFile('about.cta_s2.image2')) {
                $optionData['cta_s2']['image2'] = $this->saveFile($request, 'about.cta_s2.image2');
            } else {
                $optionData['cta_s2']['image2'] = $oldData->cta_s2?->image2 ?? null;
            }

            if ($request->hasFile('about.cta_s2.image3')) {
                $optionData['cta_s2']['image3'] = $this->saveFile($request, 'about.cta_s2.image3');
            } else {
                $optionData['cta_s2']['image3'] = $oldData->cta_s2?->image3 ?? null;
            }

            if ($request->hasFile('about.cta_s2.image4')) {
                $optionData['cta_s2']['image4'] = $this->saveFile($request, 'about.cta_s2.image4');
            } else {
                $optionData['cta_s2']['image4'] = $oldData->cta_s2?->image4 ?? null;
            }

            if ($request->hasFile('about.cta_s2.image5')) {
                $optionData['cta_s2']['image5'] = $this->saveFile($request, 'about.cta_s2.image5');
            } else {
                $optionData['cta_s2']['image5'] = $oldData->cta_s2?->image5 ?? null;
            }

            if ($request->hasFile('about.cta_s2.image6')) {
                $optionData['cta_s2']['image6'] = $this->saveFile($request, 'about.cta_s2.image6');
            } else {
                $optionData['cta_s2']['image6'] = $oldData->cta_s2?->image6 ?? null;
            }


            // video section
            if ($request->hasFile('about.video_s1.image')) {
                $optionData['video_s1']['image'] = $this->saveFile($request, 'about.video_s1.image');
            } else {
                $optionData['video_s1']['image'] = $oldData->video_s1?->image ?? null;
            }

            $data = json_encode($optionData);
            $option->value = $data;
            $option->save();
        }

        Cache::flush();

        return back();
    }
}

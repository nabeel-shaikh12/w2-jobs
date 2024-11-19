<?php

namespace App\Http\Controllers\Web;

use App\Models\Plan;
use App\Models\Post;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Services\SeoMeta;

class WebController extends Controller
{

    public function home()
    {
        if (!file_exists(base_path('public/uploads/installed'))) {
            return redirect('/install');
        }
        
        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('home', $theme_data?->home) : $theme_data?->home ?? "One";
        $theme = 'theme' . $path;
        $home = get_option($theme, true, true);

        if (!$home) {
            $home = get_option('ThemeOne', true, true) ?? [
                'hero' => [],
                'cta_s1' => [],
                'cta_s2' => [],
                'cta_s3' => [],
                'cta_s4' => [],
            ];
        }

        $fullPath = 'Web/Home/' . $path . '/Index';


        return Inertia::render($fullPath, [
            'home' => $home,
            'seo' => SeoMeta::init('seo_home', true),
        ]);
    }

    public function about()
    {
        $home = get_option('themeOne', true, true);
        $about = get_option('about-page', true, true);

        if (!$about) {
            $about = [
                'cta_s1' => [],
                'cta_s2' => [],
                'cta_s3' => [],
                'video_s1' => []
            ];
        }

        return Inertia::render('Web/About', [
            'about' => $about,
            'home' => $home,
            'seo' => SeoMeta::init('seo_about'),
        ]);
    }

    public function faq()
    {
        $faqs = Post::where('type', 'faq')->with(['excerpt', 'faq_categories:id,title,slug'])->get();
        return Inertia::render('Web/Faq', [
            'faqs' => $faqs,
            'seo' =>  SeoMeta::init('seo_faq'),
        ]);
    }

    public function pricing()
    {
        $plans = Plan::query()
            ->where('price', '>', 0)->where('status', 1)
            ->get();

        $seo = SeoMeta::init('seo_pricing');
        return Inertia::render('Web/Pricing', compact('plans', 'seo'));
    }

    public function page($slug)
    {
        $info = Post::with('description', 'seo')->where(['slug' => $slug, 'status' => 1])->firstOrFail();

        $seo = (array) json_decode($info->seo?->value ?? []);

        SeoMeta::set($seo);

        return Inertia::render('Web/CustomPage', compact('info', 'seo'));
    }
}

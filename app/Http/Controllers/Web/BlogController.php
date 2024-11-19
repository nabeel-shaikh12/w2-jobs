<?php

namespace App\Http\Controllers\Web;

use App\Models\Post;
use Inertia\Inertia;
use App\Models\Category;
use App\Services\SeoMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $blogs = Post::with('preview', 'categories:title')
            ->when(request('s'), fn ($q) => $q->where('title', 'LIKE', '%' . request('s') . '%'))
            ->where('status', 1)->where('lang', current_locale())->where('type', 'blog')->latest()->paginate(4);
        $categories = Category::where('type', 'blog_category')->whereHas('postcategories')->withCount('postcategories')->where('status', 1)->where('lang', app()->getLocale())->get();
        $tags = Category::where('type', 'tags')->whereHas('postcategories')->where('status', 1)->where('lang', app()->getLocale())->get();
        $recent_blogs = Post::where('type', 'blog')->where('lang', app()->getLocale())->with('shortDescription', 'preview')->where('status', 1)->latest()->take(4)->get();

        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->blog) : $theme_data?->blog ?? "One";
        $fullPath = 'Web/Blog/' . $path . '/Index';

        $seo = SeoMeta::init('seo_blog');

        return Inertia::render($fullPath, compact('blogs', 'categories', 'tags', 'recent_blogs', 'seo'));
    }

    public function show(Post $blog)
    {
        $blog->load('shortDescription', 'longDescription', 'tags', 'preview', 'seo', 'categories');

        $categories = Category::where('type', 'blog_category')->whereHas('postcategories')->withCount('postcategories')->where('status', 1)->where('lang', app()->getLocale())->get();

        $tags = Category::where('type', 'tags')->whereHas('postcategories')->where('status', 1)->where('lang', app()->getLocale())->get();

        $recent_blogs = Post::where('type', 'blog')->where('lang', app()->getLocale())->with('preview')->where('status', 1)->latest()->take(4)->get();

        $meta = (array) json_decode($blog->seo->value ?? '');
        $seo = SeoMeta::set($meta);

        $nextBlog = Post::where('type', 'blog')->where('id', '>', $blog->id)->where('status', 1)->first();
        $lastBlog = Post::where('type', 'blog')->where('id', '<', $blog->id)->where('status', 1)->first();

        return Inertia::render('Web/Blog/One/Show', compact('blog', 'categories', 'tags', 'recent_blogs', 'seo', 'nextBlog', 'lastBlog'));
    }

    public function category(Request $request, $slug, $id)
    {
        $category = Category::where('type', 'blog_category')->where('status', 1)->findorFail($id);

        $blogs = Post::where('type', 'blog')->where('lang', app()->getLocale());
        if (!empty($request->search)) {
            $blogs = $blogs->where('title', 'LIKE', '%' . $request->search . '%');
        }
        $blogs = $blogs->with('shortDescription', 'preview')->whereHas('postcategories', function ($query) use ($id) {
            return $query->where('category_id', $id);
        })->where('status', 1)->latest()->paginate(4);

        $recent_blogs = Post::where('type', 'blog')->where('lang', app()->getLocale())->with('shortDescription', 'preview')->where('status', 1)->latest()->take(4)->get();

        $categories = Category::where('type', 'blog_category')->where('status', 1)->whereHas('postcategories')->where('lang', app()->getLocale())->withCount('postcategories')->get();
        $tags = Category::where('type', 'tags')->where('status', 1)->whereHas('postcategories')->where('lang', app()->getLocale())->get();

        $meta['title'] = $category->title ?? '';

        $seo = SeoMeta::set($meta);

        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->blog) : $theme_data?->blog ?? "One";
        $fullPath = 'Web/Blog/' . $path . '/Index';
        return Inertia::render($fullPath, compact('blogs', 'request', 'recent_blogs', 'categories', 'tags', 'meta', 'seo'));
    }

    public function tag(Request $request, $slug, $id)
    {
        $tag = Category::where('type', 'tags')->where('status', 1)->findorFail($id);

        $blogs = Post::where('type', 'blog')->where('lang', app()->getLocale());
        if (!empty($request->search)) {
            $blogs = $blogs->where('title', 'LIKE', '%' . $request->search . '%');
        }
        $blogs = $blogs->with('shortDescription', 'preview')->whereHas('postcategories', function ($query) use ($id) {
            return $query->where('category_id', $id);
        })->where('status', 1)->latest()->paginate(4);

        $recent_blogs = Post::where('type', 'blog')->where('lang', app()->getLocale())->with('shortDescription', 'preview')->where('status', 1)->latest()->take(4)->get();

        $categories = Category::where('type', 'blog_category')->whereHas('postcategories')->where('status', 1)->withCount('postcategories')->where('lang', app()->getLocale())->get();
        $tags = Category::where('type', 'tags')->whereHas('postcategories')->where('status', 1)->where('lang', app()->getLocale())->get();

        $meta['title'] = $tag->title ?? '';

        $seo = SeoMeta::set($meta);

        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->blog) : $theme_data?->blog ?? "One";
        $fullPath = 'Web/Blog/' . $path . '/Index';
        return Inertia::render($fullPath, compact('blogs', 'request', 'recent_blogs', 'categories', 'tags', 'seo'));
    }
}

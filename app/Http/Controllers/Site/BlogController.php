<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $cat = trim((string) $request->query('category'));
        $q = trim((string) $request->query('q'));
        $posts = Blog::query()->published()->with('media')
            ->when($cat !== '', fn ($w) => $w->where('category', $cat))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('excerpt', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->latest('published_at')->latest('id')->paginate(9)->withQueryString();

        return view('site.blog', [
            'posts' => $posts, 'current' => $cat, 'q' => $q,
            'cats' => Blog::query()->published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function show(string $slug)
    {
        $post = Blog::query()->published()->with(['media', 'author'])->where('slug', $slug)->firstOrFail();
        $more = Blog::query()->published()->with('media')->where('id', '!=', $post->id)->latest('published_at')->limit(3)->get();

        return view('site.article', ['post' => $post, 'more' => $more]);
    }
}

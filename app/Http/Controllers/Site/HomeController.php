<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Product;
use App\Models\Service;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        // Sections are shown or hidden, and ordered, from Website > Homepage > Visibility & order.
        $sections = collect(config('cure.home_sections'))
            ->filter(fn ($s) => filter_var(setting('show_'.$s, true), FILTER_VALIDATE_BOOLEAN))
            ->sortBy(fn ($s) => (int) setting('order_'.$s, 99))
            ->values();

        $listed = fn () => Product::query()->active()->with(['category', 'brand', 'media']);

        return view('site.home', [
            'sections' => $sections,
            'trust' => ContentBlock::query()->section('trust')->get(),
            'why' => ContentBlock::query()->section('why')->get(),
            'categories' => Category::query()->active()->topLevel()->with('media')->orderBy('sort_order')->limit(8)->get(),
            'featured' => $listed()->where('is_featured', true)->latest('id')->limit(4)->get(),
            'popular' => $listed()->orderByDesc('views')->latest('id')->limit(8)->get(),
            'services' => Service::query()->active()->with('media')->orderBy('sort_order')->limit(6)->get(),
            'gallery' => Gallery::query()->active()->with('media')->orderBy('sort_order')->limit(6)->get(),
            'testimonials' => Testimonial::query()->active()->with('media')->orderBy('sort_order')->limit(4)->get(),
            'articles' => Blog::query()->published()->with('media')->latest('published_at')->latest('id')->limit(3)->get(),
            'faqs' => Faq::query()->active()->orderBy('sort_order')->limit(5)->get(),
        ]);
    }
}

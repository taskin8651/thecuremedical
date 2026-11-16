<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        // The homepage is limited to brands, categories, products, and testimonials.
        $sections = collect(config('cure.home_sections'))
            ->filter(fn ($s) => filter_var(setting('show_'.$s, true), FILTER_VALIDATE_BOOLEAN))
            ->sortBy(fn ($s) => (int) setting('order_'.$s, 99))
            ->values();

        $listed = fn () => Product::query()->active()->with(['category', 'brand', 'media']);

        return view('site.home', [
            'sections' => $sections,
            'brands' => Brand::query()->active()
                ->whereHas('products', fn ($q) => $q->active())
                ->with('media')
                ->withCount(['products' => fn ($q) => $q->active()])
                ->orderBy('name')->limit(10)->get(),
            'categories' => Category::query()->active()->topLevel()->with('media')->orderBy('sort_order')->limit(8)->get(),
            'products' => $listed()->orderByDesc('is_featured')->orderByDesc('views')->latest('id')->limit(8)->get(),
            'heroProducts' => $listed()->where('is_featured', true)->latest('id')->limit(6)->get(),
            'testimonials' => Testimonial::query()->active()->with('media')->orderBy('sort_order')->limit(4)->get(),
        ]);
    }
}

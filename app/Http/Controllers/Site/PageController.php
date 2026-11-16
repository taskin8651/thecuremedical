<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        $page = Page::query()->active()->where('slug', 'about')->first();
        abort_if($page === null, 404);

        return view('site.about', ['page' => $page, 'testimonials' => Testimonial::query()->active()->orderBy('sort_order')->limit(3)->get()]);
    }

    public function services()
    {
        return view('site.services', ['services' => Service::query()->active()->with('media')->orderBy('sort_order')->get()]);
    }

    public function gallery(Request $request)
    {
        $cat = trim((string) $request->query('category'));
        $type = trim((string) $request->query('type'));
        $items = collect();

        if ($type === '' || $type === 'gallery') {
            $items = $items->concat(Gallery::query()->active()->with('media')
                ->when($cat !== '', fn ($q) => $q->where('category', $cat))->orderBy('sort_order')->get()
                ->map(fn ($item) => $this->galleryCard('gallery', $item->title, $item->getFirstMediaUrl('image', 'thumb'), $item->getFirstMediaUrl('image'), $item->category)));
        }
        if ($type === '' || $type === 'products') {
            $items = $items->concat(Product::query()->active()->whereHas('media', fn ($q) => $q->where('collection_name', 'images'))
                ->with(['media', 'category', 'brand'])->orderByDesc('is_featured')->latest('id')->get()->map(function ($item) {
                    $image = $item->primaryImage();
                    return $this->galleryCard('products', $item->name, $image?->getUrl('thumb') ?: $image?->getUrl(), $image?->getUrl(), $item->category?->name);
                }));
        }
        if ($type === '' || $type === 'brands') {
            $items = $items->concat(Brand::query()->active()->whereHas('media', fn ($q) => $q->where('collection_name', 'logo'))
                ->with('media')->orderBy('name')->get()->map(fn ($item) => $this->galleryCard('brands', $item->name, $item->getFirstMediaUrl('logo', 'thumb'), $item->getFirstMediaUrl('logo'), null)));
        }
        if ($type === '' || $type === 'categories') {
            $items = $items->concat(Category::query()->active()->whereHas('media', fn ($q) => $q->where('collection_name', 'image'))
                ->with('media')->orderBy('sort_order')->get()->map(fn ($item) => $this->galleryCard('categories', $item->name, $item->getFirstMediaUrl('image', 'thumb'), $item->getFirstMediaUrl('image'), null)));
        }

        $perPage = 24;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query(),
        ]);

        return view('site.gallery', [
            'items' => $items, 'current' => $cat, 'currentType' => $type,
            'types' => ['' => 'All', 'gallery' => 'Store', 'products' => 'Products', 'brands' => 'Brands', 'categories' => 'Categories'],
            'cats' => Gallery::query()->active()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    private function galleryCard(string $type, string $title, ?string $thumb, ?string $full, ?string $category): array
    {
        return ['type' => $type, 'title' => $title, 'thumb' => $thumb, 'full' => $full, 'category' => $category];
    }

    public function contact(Request $request)
    {
        return view('site.contact', ['product' => mb_substr(trim((string) $request->query('product')), 0, 200)]);
    }

    public function faq()
    {
        return view('site.faq', ['faqs' => Faq::query()->active()->orderBy('sort_order')->get()]);
    }

    public function categories()
    {
        return view('site.categories', ['categories' => Category::query()->active()->topLevel()->with(['media', 'children' => fn ($c) => $c->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['products' => fn ($p) => $p->where('is_active', true)])->orderBy('sort_order')->get()]);
    }

    public function legal(string $slug)
    {
        $page = Page::query()->active()->where('slug', $slug)->firstOrFail();

        return view('site.page', ['page' => $page]);
    }
}

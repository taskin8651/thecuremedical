<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public catalogue. Medical details are only shown for products a pharmacist has verified,
 * prices only when "Show prices" is on, and stock only when "Show availability" is on.
 */
class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:160'], 'sort' => ['nullable', 'in:name,new,popular']]);

        $query = Product::query()->active()->with(['category', 'brand', 'media']);
        $category = null;
        if ($request->filled('category')) {
            $category = Category::query()->active()->where('slug', $request->input('category'))->first();
            if ($category) {
                $ids = $category->children()->pluck('id')->push($category->id);
                $query->where(fn ($w) => $w->whereIn('category_id', $ids)->orWhereIn('subcategory_id', $ids));
            }
        }
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('generic_name', 'like', $like)
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
        }
        match ($request->input('sort')) {
            'new' => $query->latest('id'),
            'popular' => $query->orderByDesc('views')->orderBy('name'),
            default => $query->orderBy('name'),
        };

        return view('site.products', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->active()->topLevel()->withCount(['products' => fn ($p) => $p->where('is_active', true)])->orderBy('sort_order')->get(),
            'current' => $category,
            'q' => $q,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $product = Product::query()->active()->with(['category', 'brand', 'media'])->where('slug', $slug)->firstOrFail();

        $key = 'viewed_product_'.$product->id;
        if (! $request->session()->has($key)) {
            DB::table('products')->where('id', $product->id)->increment('views');
            $request->session()->put($key, true);
        }

        $related = Product::query()->active()->with(['category', 'media'])->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))->inRandomOrder()->limit(4)->get();

        return view('site.product', ['product' => $product, 'related' => $related]);
    }
}

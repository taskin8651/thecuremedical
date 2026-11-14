<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap()
    {
        abort_unless(filter_var(setting('sitemap_enabled', true), FILTER_VALIDATE_BOOLEAN) && filter_var(setting('robots_index', true), FILTER_VALIDATE_BOOLEAN), 404);

        $xml = Cache::remember('cure.sitemap', 3600, function () {
            $urls = [];
            foreach (['site.home', 'site.products', 'site.categories', 'site.services', 'site.gallery', 'site.blog', 'site.faq', 'site.contact', 'site.about'] as $r) {
                $urls[] = [route($r), null];
            }
            foreach (Page::query()->active()->where('slug', '!=', 'about')->get(['slug', 'updated_at']) as $p) {
                $urls[] = [route('site.page', $p->slug), $p->updated_at];
            }
            foreach (Product::query()->active()->get(['slug', 'updated_at']) as $p) {
                $urls[] = [route('site.product', $p->slug), $p->updated_at];
            }
            foreach (Blog::query()->published()->get(['slug', 'updated_at']) as $b) {
                $urls[] = [route('site.article', $b->slug), $b->updated_at];
            }
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($urls as [$loc, $mod]) {
                $out .= '  <url><loc>'.e($loc).'</loc>'.($mod ? '<lastmod>'.$mod->toAtomString().'</lastmod>' : '').'</url>'."\n";
            }

            return $out.'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = ['User-agent: *'];
        if (! filter_var(setting('robots_index', true), FILTER_VALIDATE_BOOLEAN)) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /account';
            if (filter_var(setting('sitemap_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
                $lines[] = 'Sitemap: '.route('site.sitemap');
            }
        }
        if ($extra = trim((string) setting('robots_extra'))) {
            $lines[] = $extra;
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

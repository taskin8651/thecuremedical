<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Support\Settings;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase, Helpers;

    public function test_public_pages_render_from_the_database(): void
    {
        $this->seed(SiteContentSeeder::class);
        foreach (['/', '/about', '/services', '/gallery', '/contact', '/faq', '/categories', '/products', '/blog', '/privacy', '/terms', '/refund'] as $uri) {
            $this->get($uri)->assertOk();
        }
        $this->get('/')->assertSee('Everything for everyday care')->assertSee('077804 04602');
    }

    public function test_homepage_sections_can_be_hidden_from_settings(): void
    {
        $this->seed(SiteContentSeeder::class);
        $this->get('/')->assertSee('id="faq"', false);
        Settings::set('show_faq', '0');
        Settings::flush();
        $this->get('/')->assertDontSee('id="faq"', false);
    }

    public function test_prices_and_medical_details_stay_hidden_until_allowed(): void
    {
        $p = $this->product(['name' => 'Sample Tablet', 'slug' => 'sample-tablet', 'mrp' => 55, 'composition' => 'SECRET-COMPOSITION', 'is_verified' => false]);
        $this->get('/products/sample-tablet')->assertOk()->assertDontSee('55.00')->assertDontSee('SECRET-COMPOSITION')->assertSee('still being checked');

        Settings::set('show_prices', '1');
        Settings::flush();
        $this->get('/products/sample-tablet')->assertSee('55.00');

        $p->forceFill(['is_verified' => true])->save();
        $this->get('/products/sample-tablet')->assertSee('SECRET-COMPOSITION');
    }

    public function test_inactive_products_are_not_public(): void
    {
        $this->product(['name' => 'Hidden One', 'slug' => 'hidden-one', 'is_active' => false]);
        $this->get('/products/hidden-one')->assertNotFound();
        $this->get('/products')->assertDontSee('Hidden One');
    }

    public function test_enquiry_is_saved_and_honeypot_blocks_bots(): void
    {
        $this->post('/enquiry', ['type' => 'availability', 'name' => 'Asha', 'phone' => '9876543210', 'message' => 'Do you have this item in stock?'])->assertSessionHas('enquiry_success');
        $this->assertDatabaseHas('enquiries', ['name' => 'Asha', 'status' => 'new', 'source' => 'website']);

        $this->post('/enquiry', ['type' => 'general', 'name' => 'Bot', 'phone' => '9876543210', 'message' => 'Buy cheap stuff now', 'company_website' => 'http://spam.test']);
        $this->assertSame(1, Enquiry::query()->count());
    }

    public function test_enquiry_validates_input(): void
    {
        $this->post('/enquiry', ['type' => 'general', 'name' => '', 'phone' => 'abc', 'message' => 'short'])->assertSessionHasErrors(['name', 'phone', 'message']);
    }

    public function test_enquiry_message_html_is_stripped(): void
    {
        $this->post('/enquiry', ['type' => 'general', 'name' => 'Asha', 'phone' => '9876543210', 'message' => '<script>alert(1)</script>Hello there friend']);
        $this->assertStringNotContainsString('<script>', Enquiry::query()->firstOrFail()->message);
    }

    public function test_cart_and_account_screens_say_they_are_unavailable(): void
    {
        foreach (['/cart', '/checkout', '/account/login', '/account/orders', '/wishlist', '/compare'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('not available');
        }
    }

    public function test_sitemap_and_robots_follow_settings(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertOk()->assertSee('<urlset', false);
        Settings::set('robots_index', '0');
        Settings::flush();
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/sitemap.xml')->assertNotFound();
    }
}

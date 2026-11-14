<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Starter content only. No products, prices, reviews or stock are invented.
 * Legal pages and sample articles are clearly marked as drafts for the owner to review.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->pages();
        $this->blocks();
        $this->faqs();
        $this->services();
        $this->articles();
    }

    private function categories(): void
    {
        $list = [
            ['Medicines', 'bi-capsule', 'Medicines available at the store. Ask the pharmacist about any product.'],
            ['Healthcare Devices', 'bi-heart-pulse', 'Everyday home healthcare devices.'],
            ['First Aid', 'bi-bandaid', 'First aid supplies and dressings.'],
            ['Baby Care', 'bi-emoji-smile', 'Baby care products.'],
            ['Personal Care', 'bi-droplet', 'Personal care and hygiene.'],
            ['Wellness', 'bi-flower1', 'Wellness and nutrition products.'],
            ['Surgical Supplies', 'bi-scissors', 'Surgical and hospital supplies.'],
            ['Health Supplements', 'bi-box-seam', 'Supplements and vitamins.'],
        ];
        foreach ($list as $i => [$name, $icon, $desc]) {
            Category::query()->firstOrCreate(['name' => $name, 'parent_id' => null], ['icon' => $icon, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true, 'is_featured' => true]);
        }
    }

    private function pages(): void
    {
        $draft = "> **Draft for review.** This text is a starting point only. The shop owner and a qualified adviser must review and edit it before it is relied on.\n\n";
        $pages = [
            ['about', 'About us', "{{business}} is a pharmacy and medical store on Jalalpur Road, Khasganj, Bihar Sharif. Edit this story from Website > About us.", 1],
            ['privacy-policy', 'Privacy Policy', $draft."## What we collect\nWhen you send an enquiry we receive the details you enter (name, phone, email and message).\n\n## How we use it\nTo reply to your enquiry. We do not sell your details.\n\n## Contact\nAsk the store for details about the information we hold.", 2],
            ['terms-and-conditions', 'Terms & Conditions', $draft."## Using this website\nThe website provides general information about the store and its products. It does not give medical advice.\n\n## Availability\nProduct availability and prices must be confirmed with the store.", 3],
            ['return-policy', 'Return & Refund Policy', $draft."Returns and refunds depend on the product and the law that applies to medicines. Please ask the store before you buy. The owner must complete this page.", 4],
        ];
        foreach ($pages as [$slug, $title, $content, $order]) {
            Page::query()->firstOrCreate(['slug' => $slug], ['title' => $title, 'content' => str_replace('{{business}}', 'The Cure Medicall Hall', $content), 'is_active' => true, 'is_system' => true, 'sort_order' => $order]);
        }
    }

    private function blocks(): void
    {
        if (ContentBlock::query()->exists()) {
            return;
        }
        $rows = [
            ['trust', 'Local pharmacy', 'On Jalalpur Road, Khasganj, Bihar Sharif.', 'bi-geo-alt'],
            ['trust', 'Call or WhatsApp', 'Check availability before you visit.', 'bi-telephone'],
            ['trust', 'Rated 5.0 on Google', 'Based on the reviews currently shown on Google.', 'bi-star'],
            ['why', 'Ask the pharmacist', 'Talk to the store team about the products you need.', 'bi-person-check'],
            ['why', 'Simple enquiries', 'Send the product name and we will reply.', 'bi-chat-left-text'],
            ['why', 'Close to home', 'Visit the store in Khasganj, Bihar Sharif.', 'bi-house-heart'],
        ];
        foreach ($rows as $i => [$section, $title, $text, $icon]) {
            ContentBlock::query()->create(['section' => $section, 'title' => $title, 'text' => $text, 'icon' => $icon, 'sort_order' => $i + 1, 'is_active' => true]);
        }
    }

    private function faqs(): void
    {
        if (Faq::query()->exists()) {
            return;
        }
        $rows = [
            ['How can I check if a product is available?', 'Call the store or send a message on WhatsApp with the product name. We will confirm availability.'],
            ['Can I order online?', 'Online ordering is not available right now. Please call, message or visit the store.'],
            ['Do I need a prescription for every medicine?', 'Some medicines can only be given with a valid prescription. The pharmacist at the store will tell you.'],
            ['What are your opening hours?', 'See the hours on the Contact page. The store opens at 8:00 AM. Closing time is to be confirmed.'],
        ];
        foreach ($rows as $i => [$q, $a]) {
            Faq::query()->create(['question' => $q, 'answer' => $a, 'sort_order' => $i + 1, 'is_active' => true]);
        }
    }

    private function services(): void
    {
        if (Service::query()->exists()) {
            return;
        }
        $rows = [
            ['Product availability enquiries', 'bi-search', 'Ask whether a product is in stock before you visit.'],
            ['Healthcare equipment enquiries', 'bi-heart-pulse', 'Ask about home healthcare devices and supplies.'],
            ['Store assistance', 'bi-person-check', 'Talk to the team at the counter for help finding a product.'],
            ['Call and WhatsApp support', 'bi-whatsapp', 'Reach the store by phone or WhatsApp.'],
        ];
        foreach ($rows as $i => [$name, $icon, $desc]) {
            Service::query()->create(['name' => $name, 'icon' => $icon, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true]);
        }
    }

    private function articles(): void
    {
        if (Blog::query()->exists()) {
            return;
        }
        $note = "> **Sample draft.** Review and edit before publishing. General information only, not medical advice.\n\n";
        $rows = [
            ['Keeping a simple home first aid kit', 'First aid', 'Ideas for what many households keep at home.', $note."A home first aid kit usually holds dressings, antiseptic wipes, a thermometer and a list of emergency numbers. Ask the pharmacist what suits your family. Check the expiry dates on everything every few months."],
            ['Storing medicines safely at home', 'Safety', 'General tips for storing medicines.', $note."Keep medicines in their original packs, away from children, heat and damp. Read the storage instructions on the pack, and ask the pharmacist if you are unsure."],
        ];
        foreach ($rows as [$title, $cat, $excerpt, $content]) {
            Blog::query()->create(['title' => $title, 'category' => $cat, 'excerpt' => $excerpt, 'content' => $content, 'status' => 'draft']);
        }
    }
}

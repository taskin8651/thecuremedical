<?php

/*
|--------------------------------------------------------------------------
| The Cure Medicall Hall - application configuration
|--------------------------------------------------------------------------
| modules : permission matrix. Permission names are "{module}.{action}".
| nav     : admin sidebar. [label, icon, route, permission, active-pattern]
| settings: every editable site setting (stored in the `settings` table).
*/

$crud = ['view', 'create', 'edit', 'delete'];

return [

    'modules' => [
        'dashboard'     => ['Dashboard', ['view']],
        'products'      => ['Products', ['view', 'create', 'edit', 'delete', 'verify', 'import', 'export']],
        'categories'    => ['Categories', $crud],
        'brands'        => ['Brands', $crud],
        'inventory'     => ['Inventory & batches', ['view', 'create', 'edit']],
        'suppliers'     => ['Suppliers', $crud],
        'enquiries'     => ['Enquiries', [...$crud, 'export']],
        'customers'     => ['Customers', $crud],
        'carts'         => ['Carts', ['view']],
        'orders'        => ['Orders', ['view', 'edit', 'delete']],
        'payments'      => ['Payments', ['view', 'create', 'edit']],
        'coupons'       => ['Coupons', $crud],
        'shipping'      => ['Shipping zones', $crud],
        'prescriptions' => ['Prescriptions', [...$crud, 'download', 'verify']],
        'content'       => ['Homepage, About & content blocks', $crud],
        'pages'         => ['Static pages', $crud],
        'faqs'          => ['FAQs', $crud],
        'gallery'       => ['Gallery', $crud],
        'blogs'         => ['Blog', $crud],
        'testimonials'  => ['Testimonials', $crud],
        'services'      => ['Services', $crud],
        'reports'       => ['Reports', ['view', 'export']],
        'settings'      => ['Settings', ['view', 'edit', 'custom_code']],
        'users'         => ['Admin users', $crud],
        'roles'         => ['Roles & permissions', $crud],
        'activity'      => ['Activity log', ['view']],
    ],

    // Super Admin gets everything. Others are listed as module => actions.
    'roles' => [
        'Manager' => [
            'dashboard' => ['view'], 'products' => ['view', 'create', 'edit', 'delete', 'verify', 'import', 'export'],
            'categories' => $crud, 'brands' => $crud, 'inventory' => ['view', 'create', 'edit'], 'suppliers' => $crud,
            'enquiries' => [...$crud, 'export'], 'customers' => $crud, 'carts' => ['view'], 'orders' => ['view', 'edit'],
            'payments' => ['view', 'create', 'edit'], 'coupons' => $crud, 'shipping' => $crud,
            'prescriptions' => ['view', 'create', 'edit', 'download', 'verify'],
            'content' => $crud, 'pages' => $crud, 'faqs' => $crud, 'gallery' => $crud, 'blogs' => $crud,
            'testimonials' => $crud, 'services' => $crud, 'reports' => ['view', 'export'],
            'settings' => ['view', 'edit'], 'activity' => ['view'],
        ],
        'Pharmacist' => [
            'dashboard' => ['view'], 'products' => ['view', 'edit', 'verify', 'export'], 'categories' => ['view'],
            'brands' => ['view'], 'inventory' => ['view', 'create'], 'enquiries' => ['view', 'edit'],
            'customers' => ['view'], 'orders' => ['view'],
            'prescriptions' => ['view', 'create', 'edit', 'download', 'verify'], 'reports' => ['view'],
        ],
        'Inventory Manager' => [
            'dashboard' => ['view'], 'products' => ['view', 'edit', 'export'], 'categories' => ['view'],
            'brands' => ['view'], 'inventory' => ['view', 'create', 'edit'], 'suppliers' => $crud,
            'reports' => ['view', 'export'],
        ],
        'Content Manager' => [
            'dashboard' => ['view'], 'products' => ['view'], 'categories' => ['view'],
            'content' => $crud, 'pages' => $crud, 'faqs' => $crud, 'gallery' => $crud, 'blogs' => $crud,
            'testimonials' => $crud, 'services' => $crud,
        ],
        'Support Staff' => [
            'dashboard' => ['view'], 'products' => ['view'], 'enquiries' => ['view', 'create', 'edit', 'export'],
            'customers' => ['view', 'create', 'edit'], 'orders' => ['view'],
        ],
    ],

    'nav' => [
        ['label' => 'Overview', 'items' => [
            ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'dashboard.view', 'admin.dashboard'],
        ]],
        ['label' => 'Catalogue', 'items' => [
            ['Products', 'bi-capsule', 'admin.products.index', 'products.view', 'admin.products.*'],
            ['Categories', 'bi-diagram-3', 'admin.categories.index', 'categories.view', 'admin.categories.*'],
            ['Brands', 'bi-patch-check', 'admin.brands.index', 'brands.view', 'admin.brands.*'],
        ]],
        ['label' => 'Inventory', 'items' => [
            ['Stock overview', 'bi-boxes', 'admin.inventory.index', 'inventory.view', 'admin.inventory.index'],
            ['Stock movement', 'bi-arrow-left-right', 'admin.inventory.movement', 'inventory.create', 'admin.inventory.movement*'],
            ['Stock history', 'bi-clock-history', 'admin.inventory.history', 'inventory.view', 'admin.inventory.history'],
            ['Batches & expiry', 'bi-calendar-x', 'admin.inventory.batches', 'inventory.view', 'admin.inventory.batches'],
            ['Purchases', 'bi-cart-plus', 'admin.purchases.index', 'inventory.view', 'admin.purchases.*'],
            ['Suppliers', 'bi-truck', 'admin.suppliers.index', 'suppliers.view', 'admin.suppliers.*'],
        ]],
        ['label' => 'Customers', 'items' => [
            ['Enquiries', 'bi-chat-left-text', 'admin.enquiries.index', 'enquiries.view', 'admin.enquiries.*'],
            ['Prescriptions', 'bi-file-earmark-medical', 'admin.prescriptions.index', 'prescriptions.view', 'admin.prescriptions.*'],
            ['Customers', 'bi-people', 'admin.customers.index', 'customers.view', 'admin.customers.*'],
        ]],
        ['label' => 'Commerce (disabled by default)', 'items' => [
            ['Orders', 'bi-receipt', 'admin.orders.index', 'orders.view', 'admin.orders.*'],
            ['Payments', 'bi-credit-card', 'admin.payments.index', 'payments.view', 'admin.payments.*'],
            ['Carts', 'bi-bag', 'admin.carts.index', 'carts.view', 'admin.carts.*'],
            ['Coupons', 'bi-ticket-perforated', 'admin.coupons.index', 'coupons.view', 'admin.coupons.*'],
            ['Shipping zones', 'bi-geo-alt', 'admin.shipping.index', 'shipping.view', 'admin.shipping.*'],
        ]],
        ['label' => 'Website', 'items' => [
            ['Homepage', 'bi-house-heart', 'admin.content.homepage', 'content.view', 'admin.content.homepage*'],
            ['About us', 'bi-info-circle', 'admin.content.about', 'content.view', 'admin.content.about*'],
            ['Content blocks', 'bi-grid-1x2', 'admin.content.blocks.index', 'content.view', 'admin.content.blocks.*'],
            ['Services', 'bi-clipboard2-pulse', 'admin.services.index', 'services.view', 'admin.services.*'],
            ['Gallery', 'bi-images', 'admin.gallery.index', 'gallery.view', 'admin.gallery.*'],
            ['Blog', 'bi-journal-text', 'admin.blogs.index', 'blogs.view', 'admin.blogs.*'],
            ['Testimonials', 'bi-chat-quote', 'admin.testimonials.index', 'testimonials.view', 'admin.testimonials.*'],
            ['Static pages', 'bi-file-text', 'admin.pages.index', 'pages.view', 'admin.pages.*'],
            ['FAQs', 'bi-question-circle', 'admin.faqs.index', 'faqs.view', 'admin.faqs.*'],
        ]],
        ['label' => 'Insights', 'items' => [
            ['Reports', 'bi-bar-chart-line', 'admin.reports.index', 'reports.view', 'admin.reports.*'],
            ['Activity log', 'bi-activity', 'admin.activity.index', 'activity.view', 'admin.activity.*'],
        ]],
        ['label' => 'System', 'items' => [
            ['Settings', 'bi-gear', 'admin.settings.show', 'settings.view', 'admin.settings.*'],
            ['Admin users', 'bi-person-gear', 'admin.users.index', 'users.view', 'admin.users.*'],
            ['Roles & permissions', 'bi-shield-lock', 'admin.roles.index', 'roles.view', 'admin.roles.*'],
        ]],
    ],

    /*
    | Tabs for the settings screens. scope => tab => [label, keys...]
    */
    'tabs' => [
        'settings' => [
            'general'  => ['General', ['business_name', 'tagline', 'contact_email', 'phone', 'whatsapp_number', 'address', 'maps_url', 'maps_embed_url', 'opening_hours', 'google_rating', 'google_reviews']],
            'social'   => ['Social', ['facebook_url', 'instagram_url', 'youtube_url']],
            'website'  => ['Website', ['logo_header', 'logo_footer', 'favicon', 'primary_color', 'dark_color', 'announcement_enabled', 'announcement_text', 'announcement_link', 'footer_text', 'copyright_text', 'show_prices', 'show_availability']],
            'seo'      => ['SEO', ['meta_title', 'meta_description', 'meta_keywords', 'og_image', 'robots_index', 'robots_extra', 'sitemap_enabled']],
            'features' => ['Ordering & compliance', ['inventory_enabled', 'expiry_alert_days', 'ordering_enabled', 'payments_enabled', 'prescriptions_enabled', 'payment_methods']],
            'code'     => ['Custom code', ['header_scripts', 'footer_scripts', 'custom_css']],
        ],
        'homepage' => [
            'hero'         => ['Hero banner', ['hero_eyebrow', 'hero_title', 'hero_highlight', 'hero_subtitle', 'hero_image', 'hero_btn1_label', 'hero_btn1_url', 'hero_btn2_label', 'hero_btn2_url']],
            'announcement' => ['Announcement bar', ['announcement_enabled', 'announcement_text', 'announcement_link']],
            'sections'     => ['Section titles', ['home_brands_title', 'home_categories_title', 'home_categories_text', 'home_products_title', 'home_reviews_title']],
            'about'        => ['About section', ['home_about_eyebrow', 'home_about_title', 'home_about_text', 'home_about_image']],
            'promo'        => ['Promotional banner', ['promo_badge', 'promo_title', 'promo_text', 'promo_btn_label', 'promo_btn_url']],
            'newsletter'   => ['Newsletter', ['newsletter_title', 'newsletter_text']],
            'visibility'   => ['Visibility & order', ['show_brands', 'show_categories', 'show_products', 'show_reviews',
                                'order_brands', 'order_categories', 'order_products', 'order_reviews']],
        ],
    ],

    'home_sections' => ['brands', 'categories', 'products', 'reviews'],

    /*
    | Setting definitions: key => [type, label, default, rules, help]
    | types: text, textarea, email, url, number, checkbox, image, color, code
    */
    'settings' => [
        // general
        'business_name'   => ['text', 'Business name', 'The Cure Medicall Hall', 'required|string|max:120'],
        'tagline'         => ['text', 'Tagline', 'Pharmacy & Medical Store', 'nullable|string|max:160'],
        'contact_email'   => ['email', 'Contact email', null, 'nullable|email|max:150'],
        'phone'           => ['text', 'Phone', '077804 04602', 'required|string|max:30'],
        'whatsapp_number' => ['text', 'WhatsApp number', '917780404602', 'nullable|regex:/^[0-9]{10,15}$/', 'Digits only, with country code (e.g. 917780404602). Please confirm with the owner.'],
        'address'         => ['textarea', 'Address', 'Jalalpur Road, Khasganj, Bihar Sharif, Bihar 803118', 'required|string|max:400'],
        'maps_url'        => ['url', 'Google Maps URL', null, 'nullable|url|max:500', 'Used for the "View on Google Maps" link. Leave empty to build one from the address.'],
        'maps_embed_url'  => ['url', 'Google Maps embed URL', null, 'nullable|url|max:700', 'The src of the Google Maps embed iframe. Leave empty to build one from the address.'],
        'opening_hours'   => ['textarea', 'Business hours', "Opens 8:00 AM\nClosing time to be confirmed", 'nullable|string|max:500', 'One line per row. Shown on the website.'],
        'google_rating'   => ['text', 'Google rating', '5.0', 'nullable|string|max:5'],
        'google_reviews'  => ['number', 'Google review count', '4', 'nullable|integer|min:0|max:1000000'],

        // social
        'facebook_url'  => ['url', 'Facebook URL', null, 'nullable|url|max:300'],
        'instagram_url' => ['url', 'Instagram URL', null, 'nullable|url|max:300'],
        'youtube_url'   => ['url', 'YouTube URL', null, 'nullable|url|max:300'],

        // website
        'logo_header'          => ['image', 'Header logo', null, 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'],
        'logo_footer'          => ['image', 'Footer logo', null, 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'],
        'favicon'              => ['image', 'Favicon', null, 'nullable|file|mimes:png,jpg,jpeg,webp,ico|max:1024'],
        'primary_color'        => ['color', 'Primary colour', '#0D6B4B', 'required|regex:/^#[0-9a-fA-F]{6}$/'],
        'dark_color'           => ['color', 'Dark colour', '#084B37', 'required|regex:/^#[0-9a-fA-F]{6}$/'],
        'announcement_enabled' => ['checkbox', 'Show announcement bar', true, 'boolean'],
        'announcement_text'    => ['text', 'Announcement text', 'Visit us at Jalalpur Road, Khasganj, Bihar Sharif. Call to check availability.', 'nullable|string|max:250'],
        'announcement_link'    => ['url', 'Announcement link', null, 'nullable|url|max:300'],
        'footer_text'          => ['textarea', 'Footer text', 'Your neighbourhood pharmacy in Bihar Sharif. Visit, call or message us for product enquiries and store assistance.', 'nullable|string|max:500'],
        'copyright_text'       => ['text', 'Copyright text', 'The Cure Medicall Hall. All rights reserved.', 'nullable|string|max:200'],
        'show_prices'          => ['checkbox', 'Show prices on the public site', false, 'boolean', 'Keep off until the owner has confirmed prices.'],
        'show_availability'    => ['checkbox', 'Show stock availability on the public site', false, 'boolean', 'When off, products show "Ask store".'],

        // seo
        'meta_title'       => ['text', 'Default meta title', 'The Cure Medicall Hall | Pharmacy in Bihar Sharif', 'nullable|string|max:70'],
        'meta_description' => ['textarea', 'Default meta description', 'Pharmacy and medical store on Jalalpur Road, Khasganj, Bihar Sharif. Call or WhatsApp to enquire about products.', 'nullable|string|max:200'],
        'meta_keywords'    => ['text', 'Meta keywords', 'pharmacy, medical store, Bihar Sharif, Khasganj', 'nullable|string|max:250'],
        'og_image'         => ['image', 'Open Graph image', null, 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048'],
        'robots_index'     => ['checkbox', 'Allow search engines to index the site', true, 'boolean'],
        'robots_extra'     => ['textarea', 'Extra robots.txt lines', null, 'nullable|string|max:1000'],
        'sitemap_enabled'  => ['checkbox', 'Enable sitemap.xml', true, 'boolean'],

        // features / compliance
        'inventory_enabled'     => ['checkbox', 'Inventory management enabled', true, 'boolean', 'Controls low-stock alerts on the dashboard.'],
        'expiry_alert_days'     => ['number', 'Expiry alert window (days)', '60', 'required|integer|min:1|max:730'],
        'ordering_enabled'      => ['checkbox', 'Online ordering enabled', false, 'boolean', 'Keep OFF until the owner confirms licensing, prescription and delivery requirements.'],
        'payments_enabled'      => ['checkbox', 'Payment recording enabled', false, 'boolean', 'No payment gateway is connected. This only allows staff to record verified payments.'],
        'prescriptions_enabled' => ['checkbox', 'Prescription workflow enabled', false, 'boolean', 'Staff-only. There is no public prescription upload.'],
        'payment_methods'       => ['textarea', 'Payment methods', "Cash at store\nUPI to store account\nBank transfer", 'nullable|string|max:500', 'One per line. Never store card numbers.'],

        // code (super admin only)
        'header_scripts' => ['code', 'Header scripts', null, 'nullable|string|max:20000', 'Inserted before </head>. Only add code you trust.'],
        'footer_scripts' => ['code', 'Footer scripts', null, 'nullable|string|max:20000', 'Inserted before </body>. Only add code you trust.'],
        'custom_css'     => ['code', 'Custom CSS', null, 'nullable|string|max:20000'],

        // homepage
        'hero_eyebrow'    => ['text', 'Hero eyebrow', 'Pharmacy & Medical Store · Bihar Sharif', 'nullable|string|max:120'],
        'hero_title'      => ['text', 'Hero title', 'Your neighbourhood pharmacy,', 'required|string|max:160'],
        'hero_highlight'  => ['text', 'Hero highlighted words', 'made easier.', 'nullable|string|max:80'],
        'hero_subtitle'   => ['textarea', 'Hero subtitle', 'Visit The Cure Medicall Hall on Jalalpur Road for everyday health and wellness products, or call and message us to check availability before you come.', 'nullable|string|max:400'],
        'hero_image'      => ['image', 'Hero image', null, 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096', 'Leave empty to use the default illustration.'],
        'hero_btn1_label' => ['text', 'Primary button label', 'Call Now', 'nullable|string|max:40'],
        'hero_btn1_url'   => ['text', 'Primary button link', null, 'nullable|string|max:300', 'Leave empty to call the store phone number.'],
        'hero_btn2_label' => ['text', 'Secondary button label', 'WhatsApp Enquiry', 'nullable|string|max:40'],
        'hero_btn2_url'   => ['text', 'Secondary button link', null, 'nullable|string|max:300', 'Leave empty to open WhatsApp.'],

        'home_brands_title'     => ['text', 'Brands heading', 'Trusted brands', 'nullable|string|max:120'],
        'home_categories_title' => ['text', 'Categories heading', 'Everything for everyday care', 'nullable|string|max:120'],
        'home_categories_text'  => ['textarea', 'Categories text', 'Browse our catalogue by category.', 'nullable|string|max:300'],
        'home_products_title'   => ['text', 'Products heading', 'Popular products', 'nullable|string|max:120'],
        'home_why_title'        => ['text', 'Why choose us heading', 'Simple, careful, local', 'nullable|string|max:120'],
        'home_services_title'   => ['text', 'Services heading', 'How we can help', 'nullable|string|max:120'],
        'home_popular_title'    => ['text', 'Popular products heading', 'Popular healthcare products', 'nullable|string|max:120'],
        'home_gallery_title'    => ['text', 'Gallery heading', 'Inside the store', 'nullable|string|max:120'],
        'home_reviews_title'    => ['text', 'Testimonials heading', 'What our customers say', 'nullable|string|max:120'],
        'home_articles_title'   => ['text', 'Articles heading', 'Helpful reading', 'nullable|string|max:120'],
        'home_faq_title'        => ['text', 'FAQ heading', 'Common questions', 'nullable|string|max:120'],
        'home_contact_title'    => ['text', 'Contact heading', 'Find the store', 'nullable|string|max:120'],
        'home_about_eyebrow'    => ['text', 'About eyebrow', 'About The Cure Medicall Hall', 'nullable|string|max:120'],
        'home_about_title'      => ['text', 'About title', 'A local pharmacy you can walk up to and ask', 'nullable|string|max:160'],
        'home_about_text'       => ['textarea', 'About text', 'We serve the Khasganj area of Bihar Sharif from our store on Jalalpur Road.', 'nullable|string|max:1200'],
        'home_about_image'      => ['image', 'About image', null, 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096'],
        'promo_badge'           => ['text', 'Promo badge', 'Before you visit', 'nullable|string|max:60'],
        'promo_title'           => ['text', 'Promo title', 'Check availability in seconds', 'nullable|string|max:160'],
        'promo_text'            => ['textarea', 'Promo text', 'Send us the product name on WhatsApp or call the store.', 'nullable|string|max:400'],
        'promo_btn_label'       => ['text', 'Promo button label', 'Send an enquiry', 'nullable|string|max:40'],
        'promo_btn_url'         => ['text', 'Promo button link', '/contact', 'nullable|string|max:300'],
        'newsletter_title'      => ['text', 'Newsletter title', 'Get health tips by email', 'nullable|string|max:120'],
        'show_brands' => ['checkbox', 'Show brands section', true, 'boolean'],
        'show_categories' => ['checkbox', 'Show categories section', true, 'boolean'],
        'show_products' => ['checkbox', 'Show products section', true, 'boolean'],
        'show_reviews' => ['checkbox', 'Show reviews section', true, 'boolean'],
        'order_brands' => ['number', 'Position of brands section', '1', 'required|integer|min:1|max:99'],
        'order_categories' => ['number', 'Position of categories section', '2', 'required|integer|min:1|max:99'],
        'order_products' => ['number', 'Position of products section', '3', 'required|integer|min:1|max:99'],
        'order_reviews' => ['number', 'Position of testimonials section', '4', 'required|integer|min:1|max:99'],
        'newsletter_text'       => ['textarea', 'Newsletter text', 'Occasional store updates and general wellness reading.', 'nullable|string|max:300'],
    ],
];

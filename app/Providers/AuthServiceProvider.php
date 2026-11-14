<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        \App\Models\Blog::class => \App\Policies\BlogPolicy::class,
        \App\Models\Brand::class => \App\Policies\BrandPolicy::class,
        \App\Models\Cart::class => \App\Policies\CartPolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\ContentBlock::class => \App\Policies\ContentBlockPolicy::class,
        \App\Models\Coupon::class => \App\Policies\CouponPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Enquiry::class => \App\Policies\EnquiryPolicy::class,
        \App\Models\Faq::class => \App\Policies\FaqPolicy::class,
        \App\Models\Gallery::class => \App\Policies\GalleryPolicy::class,
        \App\Models\InventoryTransaction::class => \App\Policies\InventoryTransactionPolicy::class,
        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Page::class => \App\Policies\PagePolicy::class,
        \App\Models\Payment::class => \App\Policies\PaymentPolicy::class,
        \App\Models\PrescriptionEnquiry::class => \App\Policies\PrescriptionEnquiryPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\ProductBatch::class => \App\Policies\ProductBatchPolicy::class,
        \App\Models\Purchase::class => \App\Policies\PurchasePolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Service::class => \App\Policies\ServicePolicy::class,
        \App\Models\ShippingZone::class => \App\Policies\ShippingZonePolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\Testimonial::class => \App\Policies\TestimonialPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Gate::before(fn ($user) => $user->isSuperAdmin() ? true : null);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description', 250)->nullable();
            $table->string('discount_type', 10)->default('percent'); // percent | fixed
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('pincodes')->nullable();
            $table->decimal('charge', 10, 2)->default(0);
            $table->decimal('free_above', 10, 2)->nullable();
            $table->string('estimated_days', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token', 80)->nullable()->unique();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('delivery_status', 20)->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_name', 150)->nullable();
            $table->string('shipping_phone', 30)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_pincode', 12)->nullable();
            $table->date('estimated_delivery')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 200);
            $table->string('sku', 80)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        // No card data is ever stored. Only method label + reference.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('payments')->nullOnDelete(); // refund -> original
            $table->string('type', 10)->default('payment'); // payment | refund
            $table->string('method', 60);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index(); // pending|verified|failed
            $table->string('transaction_reference', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('refund_reason', 250)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('coupons');
    }
};

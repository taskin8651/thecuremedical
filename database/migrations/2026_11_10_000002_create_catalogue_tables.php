<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->string('icon', 60)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->string('manufacturer', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('sku', 80)->nullable()->unique();
            $table->string('barcode', 80)->nullable()->index();
            $table->string('short_description', 400)->nullable();
            $table->text('description')->nullable();
            $table->string('generic_name', 200)->nullable();
            $table->text('composition')->nullable();
            $table->string('strength', 100)->nullable();
            $table->string('dosage_form', 100)->nullable();
            $table->string('pack_size', 100)->nullable();
            $table->string('pack_type', 100)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('ptr', 10, 2)->nullable();
            $table->decimal('pts', 10, 2)->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('min_quantity')->default(0);
            $table->unsignedInteger('max_quantity')->nullable();
            $table->boolean('requires_prescription')->default(false)->index();
            $table->text('storage_conditions')->nullable();
            $table->string('shelf_life', 100)->nullable();
            $table->text('side_effects')->nullable();
            $table->text('contraindications')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            // Medicine information must be confirmed by the owner / an authorised pharmacist.
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'is_featured']);
            $table->index('name');
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('value', 400);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};

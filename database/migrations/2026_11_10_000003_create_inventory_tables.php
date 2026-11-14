<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_person', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 80)->nullable();
            $table->date('purchase_date');
            $table->string('batch_number', 80);
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('purchase_date');
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number', 80);
            $table->date('expiry_date')->nullable()->index();
            $table->integer('quantity')->default(0); // remaining
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'batch_number']);
        });

        // Append-only ledger. Rows are never updated or deleted (enforced in the model).
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('type', 20); // in | out | adjustment
            $table->integer('quantity'); // signed change
            $table->integer('balance_after');
            $table->string('reason', 60)->nullable();
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};

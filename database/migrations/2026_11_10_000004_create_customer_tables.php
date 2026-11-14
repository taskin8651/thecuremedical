<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable()->index();
            $table->string('email', 150)->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('status', 20)->default('active')->index(); // active | blocked
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('type', 40)->default('general');
            $table->string('product_name', 200)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new')->index(); // new|pending|contacted|resolved|closed
            $table->text('admin_notes')->nullable();
            $table->string('source', 30)->default('website');
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });

        Schema::create('prescription_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('received')->index(); // received|under_review|verified|rejected|completed
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('staff_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_enquiries');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('customers');
    }
};

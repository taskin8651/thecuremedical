<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 40)->nullable()->after('payment_status');
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('payment_method'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['password', 'email_verified_at']));
    }
};

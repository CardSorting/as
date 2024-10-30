<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_silos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('store_domain')->unique();
            $table->string('subscription_tier');
            $table->string('payment_status');
            $table->decimal('monthly_fee', 8, 2);
            $table->json('subscription_limits');
            $table->timestamp('next_billing_date');
            $table->string('timezone')->default('UTC');
            $table->string('stripe_account_id')->nullable();
            $table->boolean('payout_method_valid')->default(false);
            $table->decimal('available_balance', 10, 2)->default(0);
            $table->decimal('held_balance', 10, 2)->default(0);
            $table->decimal('revenue_share_percentage', 5, 2)->default(5.00);
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['payment_status', 'next_billing_date']);
            $table->index('subscription_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_silos');
    }
};

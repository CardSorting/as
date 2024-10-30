<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('silo_id')->constrained('store_silos')->onDelete('cascade');
            $table->string('subdomain')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->json('settings')->nullable();
            $table->json('theme_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_container_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');
            $table->json('files')->nullable();
            $table->boolean('is_digital')->default(true);
            $table->timestamps();
        });

        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_container_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_product_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('status');
            $table->json('customer_details');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // This will automatically update the silo's transaction record
            $table->foreign('order_number')->references('transaction_id')->on('silo_transactions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('store_containers');
    }
};

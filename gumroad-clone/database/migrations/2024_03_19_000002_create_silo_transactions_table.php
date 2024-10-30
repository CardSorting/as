<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('silo_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_silo_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id');
            $table->decimal('amount', 10, 2);
            $table->string('type');
            $table->timestamps();

            $table->index(['store_silo_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('silo_transactions');
    }
};

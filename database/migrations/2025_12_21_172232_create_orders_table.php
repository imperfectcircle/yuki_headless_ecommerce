<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('number')->unique(); // ORD-2025-000123
            $table->string('status')->default('draft');

            // Monetary snapshot
            $table->string('currency', 3);
            $table->integer('subtotal');
            $table->integer('tax_total');
            $table->integer('shipping_total');
            $table->integer('grand_total');

            // Customer snapshot 
            $table->string('customer_email');
            $table->string('customer_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

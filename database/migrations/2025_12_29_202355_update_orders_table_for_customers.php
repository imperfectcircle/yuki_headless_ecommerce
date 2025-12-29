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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_profile_id')
                ->nullable()
                ->after('id')
                ->constrained('customer_profiles')
                ->nullOnDelete();

            $table->string('customer_full_name')->nullable()->after('customer_email');

            $table->string('customer_phone')->nullable()->after('customer_full_name');

            $table->json('shipping_address')->nullable()->after('customer_phone');
            $table->json('billing_address')->nullable()->after('shipping_address');

            $table->boolean('guest_checkout')->default(false)->after('billing_address');

            $table->index('customer_profile_id');
            $table->index('guest_checkout');
        });

        if (Schema::hasColumn('orders', 'customer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('customer_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_profile_id']);
            $table->dropIndex(['customer_profile_id']);
            $table->dropIndex(['guest_checkout']);

            $table->dropColumn([
                'costumer_profile_id',
                'customer_full_name',
                'customer_phone',
                'shipping_address',
                'billing_address',
                'guest_checkout',
            ]);

            $table->string('customer_name')->nullable();
        });
    }
};

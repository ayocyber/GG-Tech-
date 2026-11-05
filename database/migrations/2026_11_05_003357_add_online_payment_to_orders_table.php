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
            $table->string('payment_status')->nullable()->after('payment_method');
            $table->string('payment_identifier')->nullable()->unique()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_identifier');
            $table->string('payment_provider_reference')->nullable()->after('payment_reference');
            $table->string('payment_network')->nullable()->after('payment_provider_reference');
            $table->timestamp('paid_at')->nullable()->after('payment_network');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['payment_identifier']);
            $table->dropColumn([
                'payment_status', 'payment_identifier', 'payment_reference',
                'payment_provider_reference', 'payment_network', 'paid_at',
            ]);
        });
    }
};

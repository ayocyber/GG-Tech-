<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Each online payment remembers the gateway that took it, so switching gateways
     * later never sends an old order to the wrong provider.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_status');
            $table->string('payment_transaction_id')->nullable()->after('payment_identifier')->index();
        });

        // Online orders placed so far went through the gateway the PAYGATE_MODE setting chose.
        DB::table('orders')
            ->whereNotNull('payment_status')
            ->update(['payment_gateway' => config('services.paygate.mode') === 'live' ? 'paygate' : 'demo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_transaction_id']);
            $table->dropColumn(['payment_gateway', 'payment_transaction_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GG-TECH sells in Togo only: removes Naira prices, Nigerian delivery zones and the exchange rate.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('price_ngn');
        });

        DB::table('delivery_zones')->where('market', 'ng')->delete();
        DB::table('settings')->where('key', 'ngn_per_xof')->delete();
    }

    /**
     * Reverse the migrations.
     *
     * Restores the column only; deleted Naira prices and zones are not recovered.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('price_ngn')->nullable()->after('price_xof');
        });
    }
};

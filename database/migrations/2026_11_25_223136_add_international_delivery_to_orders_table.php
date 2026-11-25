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
            $table->string('delivery_country', 2)->nullable()->after('delivery_zone_id')->index();
            $table->string('region')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_country']);
            $table->dropColumn(['delivery_country', 'region', 'postal_code']);
        });
    }
};

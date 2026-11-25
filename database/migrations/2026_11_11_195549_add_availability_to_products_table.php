<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every existing product stays Togo only until the team opens it to international delivery.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('availability')->default('togo_only')->after('status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['availability']);
            $table->dropColumn('availability');
        });
    }
};

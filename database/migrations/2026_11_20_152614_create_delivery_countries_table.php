<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Countries outside Togo that GG-TECH ships to, each with a fixed delivery fee in FCFA.
     */
    public function up(): void
    {
        Schema::create('delivery_countries', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique();
            $table->unsignedInteger('fee');
            $table->json('delivery_time')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_countries');
    }
};

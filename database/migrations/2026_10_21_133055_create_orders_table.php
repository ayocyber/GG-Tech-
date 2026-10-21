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
            $table->string('number')->nullable()->unique();
            $table->string('locale', 2);
            $table->string('market', 2);
            $table->string('currency', 3);
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone');
            $table->string('fulfilment');
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('landmark')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_fee')->nullable();
            $table->unsignedBigInteger('total');
            $table->string('status')->default('new');
            $table->string('payment_method')->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
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

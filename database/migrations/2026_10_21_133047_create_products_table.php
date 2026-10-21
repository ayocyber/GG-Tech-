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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->string('model')->nullable();
            $table->text('compatibility')->nullable();
            $table->boolean('price_on_request')->default(false);
            $table->boolean('is_heavy')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('needs_review')->default(false);
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('catalog_page')->nullable();
            $table->text('search_text')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

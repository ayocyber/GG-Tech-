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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('locale', 2);
            $table->string('market', 2);
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('job_title')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('tax_id')->nullable();
            $table->string('city')->nullable();
            $table->date('needed_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};

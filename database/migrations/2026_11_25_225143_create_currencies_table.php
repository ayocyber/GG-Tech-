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
     * Rates are stored as FCFA for one unit of the currency (1 EUR = 655.957 FCFA).
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->decimal('auto_rate', 20, 8)->nullable();
            $table->string('auto_source')->nullable();
            $table->timestamp('auto_updated_at')->nullable();
            $table->decimal('manual_rate', 20, 8)->nullable();
            $table->timestamp('manual_updated_at')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('currencies')->insert([
            [
                'code' => 'EUR',
                'auto_rate' => 655.957,
                'auto_source' => 'peg',
                'auto_updated_at' => $now,
                'is_enabled' => true,
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'USD',
                'auto_rate' => null,
                'auto_source' => null,
                'auto_updated_at' => null,
                'is_enabled' => true,
                'position' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

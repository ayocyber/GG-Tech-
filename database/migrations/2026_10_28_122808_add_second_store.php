<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (October 2026): GG-TECH has a head office and a second store.
 *
 * Moves the single shop address, hours and Maps link saved from the admin into the head office,
 * and records which store a pickup order is collected from.
 */
return new class extends Migration
{
    private const LEGACY_KEYS = ['address', 'opening_hours', 'maps_url'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_shop')->nullable()->after('fulfilment');
        });

        $legacy = Setting::query()->whereIn('key', self::LEGACY_KEYS)->pluck('value', 'key');

        if ($legacy->isEmpty()) {
            return;
        }

        $shops = config('store.shops');
        $shops['head_office'] = [...$shops['head_office'], ...$legacy->only(self::LEGACY_KEYS)->all()];

        Setting::store(['shops' => $shops]);
        Setting::query()->whereIn('key', self::LEGACY_KEYS)->get()->each->delete();
    }

    /**
     * Reverse the migrations.
     *
     * Restores the head office as the single shop address.
     */
    public function down(): void
    {
        $headOffice = Setting::query()->where('key', 'shops')->first()?->value['head_office'] ?? null;

        if ($headOffice) {
            Setting::store(collect($headOffice)->only(self::LEGACY_KEYS)->all());
            Setting::query()->where('key', 'shops')->get()->each->delete();
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_shop');
        });
    }
};

<?php

namespace Database\Seeders;

use App\Enums\Market;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Placeholder zones and fees. GG-TECH will replace the fees from the admin panel.
 */
class DeliveryZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'name' => ['fr' => 'Lomé centre', 'en' => 'Lomé centre'],
                'areas' => ['fr' => 'Amoutivé, Bè, Tokoin, Nyékonakpoè', 'en' => 'Amoutivé, Bè, Tokoin, Nyékonakpoè'],
                'fee' => 1000,
                'delivery_time' => ['fr' => '24 h', 'en' => '24 h'],
            ],
            [
                'name' => ['fr' => 'Grand Lomé', 'en' => 'Greater Lomé'],
                'areas' => ['fr' => 'Agoè, Adidogomé, Baguida', 'en' => 'Agoè, Adidogomé, Baguida'],
                'fee' => 2000,
                'delivery_time' => ['fr' => '24 – 48 h', 'en' => '24 – 48 h'],
            ],
            [
                'name' => ['fr' => 'Reste du Togo', 'en' => 'Rest of Togo'],
                'areas' => ['fr' => 'Tsévié, Kpalimé, Atakpamé, Sokodé, Kara', 'en' => 'Tsévié, Kpalimé, Atakpamé, Sokodé, Kara'],
                'fee' => 5000,
                'delivery_time' => ['fr' => '2 – 4 jours', 'en' => '2 – 4 days'],
            ],
        ];

        foreach ($zones as $position => $zone) {
            DeliveryZone::query()->updateOrCreate(
                ['market' => Market::Togo, 'name->en' => $zone['name']['en']],
                [...$zone, 'market' => Market::Togo, 'position' => $position, 'is_active' => true],
            );
        }
    }
}

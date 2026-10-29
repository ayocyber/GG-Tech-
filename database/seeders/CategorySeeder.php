<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'original-toners' => [
                'name' => ['fr' => 'Toners originaux', 'en' => 'Original toners'],
                'description' => [
                    'fr' => 'Cartouches de toner HP et Canon d’origine, fabriquées par le constructeur.',
                    'en' => 'Genuine HP and Canon toner cartridges, made by the printer manufacturer.',
                ],
                'icon' => 'toner',
            ],
            'compatible-toners' => [
                'name' => ['fr' => 'Toners compatibles premium', 'en' => 'Premium compatible toners'],
                'description' => [
                    'fr' => 'Toners compatibles de qualité premium pour HP et Canon, à prix réduit.',
                    'en' => 'Premium quality compatible toners for HP and Canon, at a lower price.',
                ],
                'icon' => 'toner',
            ],
            'inks-ribbons' => [
                'name' => ['fr' => 'Encres, rubans & consommables', 'en' => 'Inks, ribbons & supplies'],
                'description' => [
                    'fr' => 'Encres, rubans, poudres de toner et autres consommables d’impression.',
                    'en' => 'Inks, ribbons, toner powders and other printing supplies.',
                ],
                'icon' => 'toner',
            ],
            'printers' => [
                'name' => ['fr' => 'Impression & bureautique', 'en' => 'Printing & office'],
                'description' => [
                    'fr' => 'Imprimantes, photocopieurs, scanners et équipements de bureau.',
                    'en' => 'Printers, copiers, scanners and office equipment.',
                ],
                'icon' => 'printer',
            ],
            'networking' => [
                'name' => ['fr' => 'Réseau & Wi-Fi', 'en' => 'Networking & Wi-Fi'],
                'description' => [
                    'fr' => 'Routeurs, points d’accès, switchs, baies et outillage réseau.',
                    'en' => 'Routers, access points, switches, racks and network tools.',
                ],
                'icon' => 'network',
            ],
            'computing' => [
                'name' => ['fr' => 'Informatique & écrans', 'en' => 'Computers & displays'],
                'description' => [
                    'fr' => 'Ordinateurs portables, PC de bureau, écrans et vidéoprojecteurs.',
                    'en' => 'Laptops, desktop PCs, monitors and projectors.',
                ],
                'icon' => 'computer',
            ],
            'power' => [
                'name' => ['fr' => 'Énergie & protection', 'en' => 'Power & protection'],
                'description' => [
                    'fr' => 'Onduleurs, régulateurs de tension, parafoudres et batteries.',
                    'en' => 'UPS, voltage regulators, surge protectors and batteries.',
                ],
                'icon' => 'power',
            ],
            'accessories' => [
                'name' => ['fr' => 'Accessoires & stockage', 'en' => 'Accessories & storage'],
                'description' => [
                    'fr' => 'SSD, disques durs, adaptateurs, hubs USB et logiciels.',
                    'en' => 'SSDs, hard drives, adapters, USB hubs and software.',
                ],
                'icon' => 'accessory',
            ],
        ];

        foreach (array_keys($categories) as $position => $slug) {
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                [...$categories[$slug], 'position' => $position, 'is_active' => true],
            );
        }
    }
}

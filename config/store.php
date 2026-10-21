<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store details
    |--------------------------------------------------------------------------
    |
    | Default contact details for GG-TECH. Staff can override most of these
    | from the admin panel (Settings), which stores them in the database.
    |
    */

    'whatsapp' => env('STORE_WHATSAPP', '+22893424472'),

    'phone' => env('STORE_PHONE', '+22898413510'),

    'email' => env('STORE_EMAIL', 'greatgodtech@gmail.com'),

    'address' => 'Rue de France, Amoutivé, non loin du marché d\'Amoutivé, Lomé, Togo',

    'maps_url' => env('STORE_MAPS_URL', 'https://www.google.com/maps/search/?api=1&query=March%C3%A9+d%27Amoutiv%C3%A9+Lom%C3%A9+Togo'),

    'google_business_url' => env('STORE_GOOGLE_BUSINESS_URL'),

    'opening_hours' => [
        'fr' => 'Lundi – samedi, 8 h – 19 h',
        'en' => 'Monday – Saturday, 8 am – 7 pm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    'online_payments_enabled' => (bool) env('STORE_ONLINE_PAYMENTS', false),

    'bulk_quantity' => 10,

    /*
    |--------------------------------------------------------------------------
    | Product photo watermark
    |--------------------------------------------------------------------------
    */

    'watermark' => [
        'path' => resource_path('images/watermark.png'),
        'width_percent' => 18,
        'opacity' => 16,
        'margin_percent' => 4,
    ],

    'image_sizes' => [400, 800, 1200],

    /*
    | Where product photos live: local folders in development, Cloudflare R2
    | in production (STORE_IMAGES_DISK=r2_public, STORE_ORIGINALS_DISK=r2_private).
    */

    'disks' => [
        'images' => env('STORE_IMAGES_DISK', 'public'),
        'originals' => env('STORE_ORIGINALS_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | First admin account (used by the AdminUserSeeder)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@gg-tech.test'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];

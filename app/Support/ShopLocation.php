<?php

namespace App\Support;

use App\Enums\Shop;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * One physical store as shown to customers, read from the "shops" store setting.
 */
class ShopLocation
{
    /**
     * @param  array<string, string|null>  $name
     * @param  array<string, string|null>  $openingHours
     */
    public function __construct(
        public Shop $shop,
        public array $name,
        public ?string $address,
        public ?string $mapsUrl,
        public array $openingHours,
        public ?string $phone,
    ) {}

    /**
     * Stores customers can visit: the ones with an address, head office first.
     *
     * @return Collection<int, ShopLocation>
     */
    public static function listed(): Collection
    {
        return collect(Shop::cases())
            ->map(fn (Shop $shop) => static::for($shop))
            ->filter(fn (ShopLocation $location) => filled($location->address))
            ->values();
    }

    public static function for(Shop $shop): static
    {
        $details = (Setting::value('shops') ?? [])[$shop->value] ?? [];

        return new static(
            shop: $shop,
            name: $details['name'] ?? [],
            address: $details['address'] ?? null,
            mapsUrl: $details['maps_url'] ?? null,
            openingHours: $details['opening_hours'] ?? [],
            phone: $details['phone'] ?? null,
        );
    }

    /**
     * The store's own name (e.g. "Amoutivé"), or its role when staff have not named it.
     */
    public function title(): string
    {
        return ($this->name[app()->getLocale()] ?? null) ?: ($this->name['fr'] ?? null) ?: $this->shop->getLabel();
    }

    public function hours(): ?string
    {
        return ($this->openingHours[app()->getLocale()] ?? null) ?: ($this->openingHours['fr'] ?? null);
    }

    /**
     * The store's own number, or the main GG-TECH number.
     */
    public function phoneNumber(): string
    {
        return $this->phone ?: (string) Setting::value('phone');
    }

    /**
     * The Google Maps link from GG-TECH, or a Maps search for the address until they send one.
     */
    public function directionsUrl(): string
    {
        return $this->mapsUrl ?: 'https://www.google.com/maps/search/?api=1&query='.urlencode((string) $this->address);
    }

    public function embedUrl(): string
    {
        return 'https://www.google.com/maps?q='.urlencode((string) $this->address).'&output=embed';
    }
}

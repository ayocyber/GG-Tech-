<?php

use App\Models\Setting;

function setSecondStore(array $details): void
{
    $shops = config('store.shops');
    $shops['second_store'] = [...$shops['second_store'], ...$details];

    Setting::store(['shops' => $shops]);
}

it('shows only the head office until the second store has an address', function () {
    $this->get('/fr/store')
        ->assertOk()
        ->assertSee('Notre boutique')
        ->assertSee('Siège')
        ->assertSee('Rue de France, Amoutivé', escape: false)
        ->assertDontSee('Deuxième boutique');
});

it('lists both stores, each labelled and with its own Google Maps link', function () {
    setSecondStore([
        'name' => ['fr' => 'Bè', 'en' => 'Bè'],
        'address' => 'Boulevard du 13 Janvier, Bè, Lomé',
        'maps_url' => 'https://maps.app.goo.gl/second-store',
    ]);

    $this->get('/fr/store')
        ->assertOk()
        ->assertSee('Nos boutiques')
        ->assertSeeInOrder(['Siège', 'Amoutivé', 'Deuxième boutique', 'Bè', 'Boulevard du 13 Janvier, Bè, Lomé'])
        ->assertSee('https://maps.app.goo.gl/second-store');
});

it('searches the address on Google Maps until GG-TECH sends the store link', function () {
    setSecondStore(['address' => 'Adidogomé, Lomé']);

    $this->get('/en/store')
        ->assertOk()
        ->assertSee('Second store')
        ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=Adidogom%C3%A9%2C+Lom%C3%A9', escape: false);
});

it('shows both stores in the footer of every page', function () {
    setSecondStore(['name' => ['fr' => 'Bè', 'en' => 'Bè'], 'address' => 'Boulevard du 13 Janvier, Bè, Lomé']);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Head office · Amoutivé')
        ->assertSee('Second store · Bè');
});

<?php

use App\Livewire\QuoteForm;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\QuoteRequestReceived;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\Baskets\QuoteBasket;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('sends a quote request with basket products and extra items', function () {
    Mail::fake();
    $product = Product::factory()->priced()->create(['name' => ['fr' => 'Switch Gigabit 8 ports', 'en' => '8-port Gigabit switch']]);
    app(QuoteBasket::class)->add($product->variants->first(), 20);

    Livewire::test(QuoteForm::class)
        ->set('company_name', 'Société Lomé Services')
        ->set('contact_name', 'Yao Agbeko')
        ->set('email', 'achats@lomeservices.tg')
        ->set('phone', '+228 22 21 00 00')
        ->call('addExtraItem')
        ->set('extraItems.0.description', 'Câble CAT6 305 m')
        ->set('extraItems.0.quantity', 5)
        ->call('submit')
        ->assertHasNoErrors();

    $quote = QuoteRequest::query()->with('items')->sole();

    expect($quote)
        ->company_name->toBe('Société Lomé Services')
        ->number->toBe('Q-'.($quote->id + 1000))
        ->and($quote->items->pluck('quantity', 'description')->all())
        ->toBe(['Switch Gigabit 8 ports' => 20, 'Câble CAT6 305 m' => 5])
        ->and(app(QuoteBasket::class)->isEmpty())->toBeTrue();

    Mail::assertQueued(QuoteRequestReceived::class, fn (QuoteRequestReceived $mail) => $mail->hasTo('achats@lomeservices.tg'));
    Mail::assertQueued(NewQuoteRequestAlert::class);
});

it('asks for at least one item', function () {
    Livewire::test(QuoteForm::class)
        ->set('company_name', 'Société Lomé Services')
        ->set('contact_name', 'Yao Agbeko')
        ->set('email', 'achats@lomeservices.tg')
        ->set('phone', '+228 22 21 00 00')
        ->call('submit')
        ->assertHasErrors('items')
        ->assertSee('Ajoutez au moins un produit ou décrivez votre besoin.');

    expect(QuoteRequest::query()->count())->toBe(0);
});

it('requires the company and contact details', function () {
    Livewire::test(QuoteForm::class)
        ->set('phone', '')
        ->call('submit')
        ->assertHasErrors([
            'company_name' => 'required',
            'contact_name' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);
});

it('ignores blank extra item lines', function () {
    Mail::fake();

    Livewire::test(QuoteForm::class)
        ->set('company_name', 'Société Lomé Services')
        ->set('contact_name', 'Yao Agbeko')
        ->set('email', 'achats@lomeservices.tg')
        ->set('phone', '+228 22 21 00 00')
        ->call('addExtraItem')
        ->call('addExtraItem')
        ->set('extraItems.1.description', 'Onduleur 3 kVA')
        ->call('submit')
        ->assertHasNoErrors();

    expect(QuoteRequest::query()->sole()->items()->pluck('description')->all())->toBe(['Onduleur 3 kVA']);
});

<?php

namespace App\Livewire;

use App\Actions\SubmitQuoteRequest;
use App\Enums\Market;
use App\Services\Baskets\QuoteBasket;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Livewire\Component;

class QuoteForm extends Component
{
    public string $company_name = '';

    public string $contact_name = '';

    public string $job_title = '';

    public string $email = '';

    public string $phone = '';

    public string $tax_id = '';

    public string $city = '';

    public string $needed_by = '';

    public string $notes = '';

    /**
     * Items the customer wants that are not on the website.
     *
     * @var list<array{description: string, quantity: int|string}>
     */
    public array $extraItems = [];

    public function mount(): void
    {
        $this->phone = Market::current()->phonePrefix().' ';
    }

    public function addExtraItem(): void
    {
        if (count($this->extraItems) < 20) {
            $this->extraItems[] = ['description' => '', 'quantity' => 1];
        }
    }

    public function removeExtraItem(int $index): void
    {
        unset($this->extraItems[$index]);
        $this->extraItems = array_values($this->extraItems);
    }

    public function updateQuantity(QuoteBasket $basket, int $variantId, int|string $quantity): void
    {
        $basket->update($variantId, (int) $quantity);
        $this->dispatch('basket-updated');
    }

    public function remove(QuoteBasket $basket, int $variantId): void
    {
        $basket->remove($variantId);
        $this->dispatch('basket-updated');
    }

    public function submit(SubmitQuoteRequest $submitQuoteRequest, QuoteBasket $basket): void
    {
        $this->extraItems = array_values(array_filter(
            $this->extraItems,
            fn (array $item) => trim($item['description']) !== '',
        ));

        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-.]{8,}$/'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:120'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'extraItems' => ['array', 'max:20'],
            'extraItems.*.description' => ['required', 'string', 'max:255'],
            'extraItems.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        if ($basket->isEmpty() && $this->extraItems === []) {
            $this->addError('items', __('Add at least one product or describe what you need.'));

            return;
        }

        $details = collect($validated)->except('extraItems')->map(fn ($value) => $value === '' ? null : $value)->all();
        $extraItems = collect($this->extraItems)
            ->map(fn (array $item) => ['description' => trim($item['description']), 'quantity' => (int) $item['quantity']])
            ->all();

        $quote = $submitQuoteRequest->handle($details, $extraItems, Market::current());

        $this->dispatch('basket-updated');
        $this->redirect(URL::signedRoute('quotes.thanks', ['quoteRequest' => $quote]));
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'company_name' => __('company'),
            'contact_name' => __('contact name'),
            'email' => __('email'),
            'phone' => __('phone'),
            'needed_by' => __('date needed'),
            'extraItems.*.description' => __('item'),
            'extraItems.*.quantity' => __('quantity'),
        ];
    }

    public function render(QuoteBasket $basket): View
    {
        return view('livewire.quote-form', [
            'lines' => $basket->lines(Market::current()),
        ]);
    }
}

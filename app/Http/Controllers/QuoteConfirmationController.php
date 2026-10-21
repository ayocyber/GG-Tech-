<?php

namespace App\Http\Controllers;

use App\Models\QuoteRequest;
use Illuminate\View\View;

class QuoteConfirmationController extends Controller
{
    public function __invoke(QuoteRequest $quoteRequest): View
    {
        return view('quotes.thanks', ['quote' => $quoteRequest->load('items')]);
    }
}

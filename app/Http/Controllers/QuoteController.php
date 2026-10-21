<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __invoke(): View
    {
        return view('quotes.create');
    }
}

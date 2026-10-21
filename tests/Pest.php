<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against a fresh database. Livewire component tests skip
| the SetLocale middleware, so the French URL prefix is set as a default.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => URL::defaults(['locale' => 'fr']))
    ->in('Feature');

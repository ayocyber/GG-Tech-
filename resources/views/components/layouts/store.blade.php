@props(['title' => null, 'description' => null])

@php
    use App\Models\Setting;
    use App\Support\LocaleSwitcher;
    use App\Support\WhatsApp;

    $pageTitle = $title ? $title.' · GG-TECH' : 'GG-TECH · '.__('IT equipment in Lomé, Togo');
    $pageDescription = $description ?? __('Toners, printers, networking, computers and power equipment. Genuine products, fast delivery across Togo, free pickup in Amoutivé, Lomé.');
    $otherLocale = app()->getLocale() === 'fr' ? 'en' : 'fr';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:image" content="{{ asset('images/gg-tech-logo-full.png') }}">
        <meta name="theme-color" content="#0a1033">
        <link rel="icon" href="{{ asset('images/gg-tech-logo.png') }}">
        <link rel="alternate" hreflang="{{ $otherLocale }}" href="{{ LocaleSwitcher::urlFor($otherLocale) }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white">
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2">{{ __('Skip to content') }}</a>

        {{-- Top strip --}}
        <div class="bg-navy text-white/85">
            <div class="container-page flex h-10 items-center justify-between gap-4 text-xs">
                <p class="flex min-w-0 items-center gap-2 truncate">
                    <x-glyph name="truck" class="size-4 text-white/60" />
                    <span class="truncate">{{ __('Delivery across Togo · Free pickup in Amoutivé, Lomé') }}</span>
                </p>

                <div class="flex shrink-0 items-center gap-4">
                    <a href="tel:{{ Setting::value('phone') }}" class="hidden items-center gap-1.5 hover:text-white md:flex">
                        <x-glyph name="phone" class="size-3.5" />
                        {{ Setting::value('phone') }}
                    </a>

                    <nav class="flex items-center overflow-hidden rounded-full border border-white/20 text-[11px] font-semibold" aria-label="{{ __('Language') }}">
                        @foreach (['fr' => 'FR', 'en' => 'EN'] as $code => $short)
                            <a href="{{ LocaleSwitcher::urlFor($code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
                               @class(['px-2.5 py-1 transition', 'bg-white text-navy' => app()->getLocale() === $code, 'hover:bg-white/10' => app()->getLocale() !== $code])
                               @if (app()->getLocale() === $code) aria-current="true" @endif>{{ $short }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        {{-- Header --}}
        <header x-data="{ menuOpen: false, searchOpen: false }" class="sticky top-0 z-40 border-b border-silver/50 bg-white/90 backdrop-blur-lg">
            <div class="container-page flex h-[76px] items-center gap-4 lg:gap-8">
                <button type="button" class="-ml-2 rounded-full p-2 text-navy lg:hidden" x-on:click="menuOpen = true" aria-label="{{ __('Open menu') }}">
                    <x-glyph name="menu" class="size-6" />
                </button>

                <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ __('GG-TECH home') }}">
                    <x-logo class="h-10 w-auto sm:h-11" />
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Main') }}">
                    <div class="relative" x-data="{ open: false }" x-on:mouseenter="open = true" x-on:mouseleave="open = false">
                        <a href="{{ route('shop') }}" class="flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-semibold text-navy hover:bg-pearl" x-on:focus="open = true">
                            {{ __('Shop') }}
                            <x-glyph name="chevron-down" class="size-4 text-muted" />
                        </a>
                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="absolute left-0 top-full w-[560px] pt-3">
                            <div class="grid grid-cols-2 gap-1 rounded-2xl border border-silver/60 bg-white p-3 shadow-[0_24px_60px_-30px_rgba(10,16,51,0.45)]">
                                @foreach ($menuCategories as $menuCategory)
                                    <a href="{{ route('shop', $menuCategory) }}" class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-pearl">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-royal-50 text-royal">
                                            <x-glyph :name="$menuCategory->icon" />
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-navy">{{ $menuCategory->translate('name') }}</span>
                                            <span class="mt-0.5 line-clamp-2 block text-xs text-muted">{{ $menuCategory->translate('description') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('shop', 'toners') }}" class="rounded-full px-3.5 py-2 text-sm font-semibold text-navy hover:bg-pearl">{{ __('Toners') }}</a>
                    <a href="{{ route('quote') }}" class="rounded-full px-3.5 py-2 text-sm font-semibold text-navy hover:bg-pearl">{{ __('Business quote') }}</a>
                    <a href="{{ route('store') }}" class="rounded-full px-3.5 py-2 text-sm font-semibold text-navy hover:bg-pearl">{{ __('Our shop') }}</a>
                </nav>

                <form action="{{ route('shop') }}" method="GET" role="search" class="ml-auto hidden max-w-sm flex-1 md:block">
                    <label for="header-search" class="sr-only">{{ __('Search') }}</label>
                    <div class="relative">
                        <x-glyph name="search" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input id="header-search" name="q" type="search" value="{{ request('q') }}"
                               placeholder="{{ __('Search for a toner, a printer…') }}"
                               class="w-full rounded-full border border-silver/80 bg-pearl/70 py-2.5 pl-10 pr-4 text-sm placeholder:text-muted/80 focus:border-royal focus:bg-white focus:outline-none focus:ring-4 focus:ring-royal/10">
                    </div>
                </form>

                <div class="ml-auto flex items-center gap-1 md:ml-0">
                    <button type="button" class="rounded-full p-2.5 text-navy hover:bg-pearl md:hidden" x-on:click="searchOpen = ! searchOpen" aria-label="{{ __('Search') }}">
                        <x-glyph name="search" class="size-6" />
                    </button>
                    <livewire:basket-indicator />
                </div>
            </div>

            <div x-cloak x-show="searchOpen" x-transition class="border-t border-silver/50 bg-white px-4 py-3 md:hidden">
                <form action="{{ route('shop') }}" method="GET" role="search">
                    <label for="mobile-search" class="sr-only">{{ __('Search') }}</label>
                    <input id="mobile-search" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Search for a toner, a printer…') }}" class="field-input rounded-full">
                </form>
            </div>

            {{-- Mobile menu --}}
            <div x-cloak x-show="menuOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-navy/60" x-on:click="menuOpen = false" x-show="menuOpen" x-transition.opacity></div>
                <div class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col bg-white shadow-2xl" x-show="menuOpen"
                     x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
                    <div class="flex h-[76px] items-center justify-between border-b border-silver/50 px-4">
                        <x-logo class="h-9 w-auto" />
                        <button type="button" class="rounded-full p-2 text-navy" x-on:click="menuOpen = false" aria-label="{{ __('Close menu') }}">
                            <x-glyph name="close" class="size-6" />
                        </button>
                    </div>
                    <nav class="flex-1 overflow-y-auto p-4">
                        <p class="eyebrow mb-2 px-2">{{ __('Categories') }}</p>
                        @foreach ($menuCategories as $menuCategory)
                            <a href="{{ route('shop', $menuCategory) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-sm font-semibold text-navy hover:bg-pearl">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-royal-50 text-royal"><x-glyph :name="$menuCategory->icon" class="size-4.5" /></span>
                                {{ $menuCategory->translate('name') }}
                            </a>
                        @endforeach
                        <div class="my-4 border-t border-silver/50"></div>
                        <a href="{{ route('shop') }}" class="block rounded-xl px-2 py-2.5 text-sm font-semibold text-navy hover:bg-pearl">{{ __('All products') }}</a>
                        <a href="{{ route('quote') }}" class="block rounded-xl px-2 py-2.5 text-sm font-semibold text-navy hover:bg-pearl">{{ __('Business quote') }}</a>
                        <a href="{{ route('store') }}" class="block rounded-xl px-2 py-2.5 text-sm font-semibold text-navy hover:bg-pearl">{{ __('Our shop in Lomé') }}</a>
                    </nav>
                    <div class="border-t border-silver/50 p-4">
                        <a href="{{ WhatsApp::url() }}" target="_blank" rel="noopener" class="btn-whatsapp w-full">
                            <x-glyph name="chat" />
                            {{ __('Chat on WhatsApp') }}
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main id="content">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="mt-24 bg-navy text-white/75">
            <div class="container-page grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-logo variant="light" class="h-12 w-auto" />
                    <p class="mt-5 max-w-xs text-sm leading-6">{{ __('Genuine IT equipment for companies, resellers and individuals. Technology · Solutions · Trust.') }}</p>
                    <a href="{{ WhatsApp::url() }}" target="_blank" rel="noopener" class="btn-whatsapp mt-6">
                        <x-glyph name="chat" />
                        {{ __('Chat on WhatsApp') }}
                    </a>
                </div>

                <div class="lg:col-span-3">
                    <h2 class="font-sans text-sm font-semibold text-white">{{ __('Shop') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($menuCategories as $menuCategory)
                            <li><a href="{{ route('shop', $menuCategory) }}" class="hover:text-white">{{ $menuCategory->translate('name') }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h2 class="font-sans text-sm font-semibold text-white">{{ __('Services') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('quote') }}" class="hover:text-white">{{ __('Business quote') }}</a></li>
                        <li><a href="{{ route('store') }}" class="hover:text-white">{{ __('In-store pickup') }}</a></li>
                        <li><a href="{{ route('store') }}#delivery" class="hover:text-white">{{ __('Delivery') }}</a></li>
                        <li><a href="{{ route('cart') }}" class="hover:text-white">{{ __('My cart') }}</a></li>
                    </ul>
                </div>

                <div class="lg:col-span-3">
                    <h2 class="font-sans text-sm font-semibold text-white">{{ __('Visit us') }}</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex gap-2.5"><x-glyph name="map-pin" class="mt-0.5 size-4 text-white/50" /> <span>{{ Setting::value('address') }}</span></li>
                        <li class="flex gap-2.5"><x-glyph name="clock" class="mt-0.5 size-4 text-white/50" /> <span>{{ Setting::value('opening_hours')[app()->getLocale()] ?? '' }}</span></li>
                        <li class="flex gap-2.5"><x-glyph name="phone" class="mt-0.5 size-4 text-white/50" /> <a href="tel:{{ Setting::value('phone') }}" class="hover:text-white">{{ Setting::value('phone') }}</a></li>
                        <li class="flex gap-2.5"><x-glyph name="mail" class="mt-0.5 size-4 text-white/50" /> <a href="mailto:{{ Setting::value('email') }}" class="hover:text-white">{{ Setting::value('email') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="container-page flex flex-col gap-2 py-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ now()->year }} Great God Technology (GG-TECH). {{ __('All rights reserved.') }}</p>
                    <p>{{ __('Prices in FCFA. Online payment coming soon.') }}</p>
                </div>
            </div>
        </footer>

        {{-- Floating WhatsApp --}}
        <div x-data="{ open: false }" class="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3">
            <div x-cloak x-show="open" x-transition.origin.bottom.right x-on:click.outside="open = false"
                 class="w-72 overflow-hidden rounded-2xl border border-silver/60 bg-white shadow-[0_24px_60px_-20px_rgba(10,16,51,0.5)]">
                <div class="bg-whatsapp px-4 py-3 text-white">
                    <p class="text-sm font-semibold">{{ __('How can we help?') }}</p>
                    <p class="text-xs text-white/85">{{ Setting::value('opening_hours')[app()->getLocale()] ?? '' }}</p>
                </div>
                <div class="divide-y divide-silver/50">
                    @foreach ([__('Sales') => __('Hello GG-TECH, I have a question about a product.'), __('Business quote') => __('Hello GG-TECH, I would like a quote for my company.'), __('After-sales support') => __('Hello GG-TECH, I need help with a product I bought.')] as $topic => $message)
                        <a href="{{ WhatsApp::url($message) }}" target="_blank" rel="noopener" class="flex items-center justify-between px-4 py-3 text-sm font-medium text-ink hover:bg-pearl">
                            {{ $topic }}
                            <x-glyph name="chevron-right" class="size-4 text-muted" />
                        </a>
                    @endforeach
                </div>
            </div>
            <button type="button" x-on:click="open = ! open" class="flex size-14 items-center justify-center rounded-full bg-whatsapp text-white shadow-lg shadow-whatsapp/30 transition hover:scale-105" aria-label="{{ __('Chat on WhatsApp') }}" :aria-expanded="open">
                <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>
            </button>
        </div>

        @livewireScripts
    </body>
</html>

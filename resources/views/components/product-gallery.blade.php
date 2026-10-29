@props(['product', 'alt'])

@php
    $images = $product->images;
    $count = $images->count();
@endphp

@if ($count === 0)
    <div class="relative aspect-square overflow-hidden rounded-[1.75rem] border border-silver/60 bg-white">
        <x-product-image :alt="$alt" :icon="$product->category->icon" :model="$product->model" :brand="$product->brand?->name" />
    </div>
@else
    {{-- Native scroll snapping gives smooth touch swiping; Alpine keeps the arrows, dots and thumbnails in sync. --}}
    <div
        x-data="{
            active: 0,
            count: {{ $count }},
            go(index) {
                index = (index + this.count) % this.count;
                this.$refs.track.scrollTo({ left: index * this.$refs.track.clientWidth, behavior: 'smooth' });
                this.active = index;
            },
            sync() {
                this.active = Math.round(this.$refs.track.scrollLeft / this.$refs.track.clientWidth);
            },
        }"
        x-on:keydown.arrow-left.prevent="go(active - 1)"
        x-on:keydown.arrow-right.prevent="go(active + 1)"
        role="region"
        aria-roledescription="carousel"
        aria-label="{{ __('Product photos') }}"
    >
        <div class="group relative aspect-square overflow-hidden rounded-[1.75rem] border border-silver/60 bg-white">
            <div x-ref="track" x-on:scroll.debounce.60ms="sync()" tabindex="0"
                 class="no-scrollbar flex size-full snap-x snap-mandatory overflow-x-auto overscroll-x-contain focus:outline-none">
                @foreach ($images as $image)
                    <div class="size-full shrink-0 snap-center p-4" role="group" aria-roledescription="slide"
                         aria-label="{{ __('Photo :number of :total', ['number' => $loop->iteration, 'total' => $count]) }}">
                        <x-product-image :image="$image" :alt="$loop->first ? $alt : $alt.' — '.__('Photo :number', ['number' => $loop->iteration])"
                                         sizes="(min-width: 1024px) 55vw, 100vw" :eager="$loop->first" />
                    </div>
                @endforeach
            </div>

            @if ($count > 1)
                <span class="pointer-events-none absolute right-4 top-4 rounded-full bg-navy/75 px-2.5 py-1 text-xs font-semibold text-white" aria-hidden="true">
                    <span x-text="active + 1">1</span> / {{ $count }}
                </span>

                <button type="button" x-on:click="go(active - 1)" aria-label="{{ __('Previous photo') }}"
                        class="absolute left-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-navy shadow-md ring-1 ring-silver/60 transition hover:bg-white sm:flex sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100">
                    <x-glyph name="chevron-right" class="size-5 rotate-180" />
                </button>
                <button type="button" x-on:click="go(active + 1)" aria-label="{{ __('Next photo') }}"
                        class="absolute right-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-navy shadow-md ring-1 ring-silver/60 transition hover:bg-white sm:flex sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100">
                    <x-glyph name="chevron-right" class="size-5" />
                </button>

                <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5 sm:hidden">
                    @foreach ($images as $image)
                        <button type="button" x-on:click="go({{ $loop->index }})" aria-label="{{ __('Photo :number', ['number' => $loop->iteration]) }}"
                                class="h-2 rounded-full transition-all"
                                :class="active === {{ $loop->index }} ? 'w-5 bg-royal' : 'w-2 bg-silver'"></button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($count > 1)
            <div class="no-scrollbar mt-4 hidden gap-3 overflow-x-auto sm:flex">
                @foreach ($images as $image)
                    <button type="button" x-on:click="go({{ $loop->index }})"
                            class="size-20 shrink-0 overflow-hidden rounded-xl border-2 bg-white p-1 transition"
                            :class="active === {{ $loop->index }} ? 'border-royal' : 'border-silver/60 hover:border-royal/40'"
                            :aria-current="active === {{ $loop->index }}"
                            aria-label="{{ __('Photo :number', ['number' => $loop->iteration]) }}">
                        <x-product-image :image="$image" :alt="''" sizes="80px" />
                    </button>
                @endforeach
            </div>
        @endif
    </div>
@endif

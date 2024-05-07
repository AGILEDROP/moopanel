@props([
    'toggable' => false
])

<div>
    <div class="divide-y divide-gray-200 dark:divide-white/10">
        <div class="flex items-center justify-between gap-x-4 px-6 py-4">
            {{$toggable}}

            <div class="ms-auto flex items-center gap-x-4">
                <x-filament::input.wrapper
                    inline-prefix
                    prefix-icon="heroicon-m-magnifying-glass"
                >
                    <x-filament::input
                        inline-prefix
                        type="search"
                        wire:model.live="search"
                        :placeholder="__('Start typing to search...')"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes(
                                new \Illuminate\View\ComponentAttributeBag([
                                    'x-model.debounce.' . 1000 => 'search',
                                ])
                            )
                        "
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-4 p-6']) }}>
            {{ $gridItems }}
        </div>
    </div>

    @if($this->getRecords()->count() === 0)
        <div class="text-lg text-gray-500 dark:text-gray-400 px-6 py-12 md:min-h-[480px] flex justify-center items-center "
        >
            <div class="mx-auto grid max-w-lg justify-items-center text-center">
                <div class="mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                    <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h4 class="font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('There are no matching results!') }}
                </h4>
            </div>
        </div>
    @endif
</div>

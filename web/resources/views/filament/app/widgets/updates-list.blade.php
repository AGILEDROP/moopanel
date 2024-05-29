<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Updates') }}
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="type">
                    <option value="core" @selected($this->type == 'core') >Core</option>
                    <option value="plugins" @selected($this->type == 'plugins') >Plugins</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        <div class="flex w-full flex-col gap-y-4 py-1-5">
            @foreach($this->getList() as $listItem)
                <div class="ps-1.5 pe-1.5 block w-full">
                    <div class="flex-1 w-full">
                        <div class="flex flex-row items-start gap-6">
                            <div class="flex h-16 w-16 rounded-md ring-white justify-center justify-items-center dark:ring-gray-900
                                @if($listItem['item_type'] == 'available') bg-primary-500 @else !bg-gray-700 dark:bg-gray-700 @endif"
                            >
                                <x-fas-cube class="text-white h-8 w-8 my-auto"></x-fas-cube>
                            </div>

                            <div class="flex flex-col">
                                <div class="flex flex-row gap-3">
                                    <span class="text-lg text-base font-semibold searchable-card-label mb-1.5 @if($listItem['item_type'] == 'available') text-primary-500 dark:text-primary-400 @else text-gray-950 @endif">
                                        {{ $listItem['name'] }}
                                    </span>

                                    @isset($listItem['maturity'])
                                        <x-filament::badge size="sm" color="{{ $listItem['maturity']->getDisplayColor() }}">
                                            {{ $listItem['maturity']->toReadableString() }}
                                        </x-filament::badge>
                                    @endisset
                                </div>

                                <span class="text-md leading-6 @if($listItem['item_type'] == 'available') text-primary-500 dark:text-primary-400 @else text-gray-9500 @endif">
                                    {{ $listItem['date'] }}
                                </span>
                            </div>

                            <x-fas-chevron-right class="ms-auto my-auto h-4 w-4 text-gray-400"></x-fas-chevron-right>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

@props(['record'])

<div
    class="relative h-full bg-white transition duration-75 dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-white/10 rounded-xl shadow-sm dark:bg-white/5 ring-1 dark:hover:ring-white/20 ring-gray-950/5 dark:ring-white/10">
    <div class="flex items-center">
        <div class="flex w-full flex-col gap-y-4 py-10 px-8 md:min-h-[325px] lg:min-h-[250px]">
            <div class="block w-full">
                <x-filament::link tag="a" :href="$record->url" target="_blank"
                    class="invisible md:visible md:absolute md:top-8 md:right-8">
                    {{ __('Release notes') }}
                </x-filament::link>

                <div>
                    <div class="text-2xl font-medium mb-2 flex">
                        {{ __('Moodle Update :release', ['release' => $record->release]) }}
                        <x-filament::badge size="xs" class="ms-3 px-3"
                            color="{{ $record->maturity->getDisplayColor() }}">
                            {{ $record->maturity->toReadableString() }}
                        </x-filament::badge>
                    </div>
                    <div>
                        {{ $record->date }}
                    </div>
                </div>

                <div class="block mt-6 md:mt-0 md:absolute md:bottom-[2.5rem] md:max-w-[65%]">
                    {{ __('Selection includes') }}:
                    <span class="italic">
                        @foreach ($record->instances as $updateInstance)
                            {{ $updateInstance->name . ' (' . $updateInstance->cluster->name . ')' }}@if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </span>
                </div>

                <div
                    class="flex mt-8 mt-6 flex shrink-0 items-center flex-wrap sm:flex-nowrap justify-start md:mt-0 md:absolute md:bottom-[2.5rem] md:right-8">

                    {{-- Uncomment "Update" button --}}
                    {{-- {{ ($this->updateAction)(['updateid' => $record->id]) }} --}}

                    
                    <x-filament::link tag="a" :href="$record->url" target="_blank" class="md:hidden ml-3 md:ml-0">
                        {{ __('Release notes') }}
                    </x-filament::link>
                    
                    <x-filament-actions::modals />
                </div>
            </div>
        </div>
    </div>
</div>

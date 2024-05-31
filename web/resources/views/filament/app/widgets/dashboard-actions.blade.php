<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Actions') }}
        </x-slot>

        <ul class="-mx-2 flex flex-col gap-y-7">
            <li class="flex flex-col gap-y-1">
                <ul class="flex flex-col gap-y-1">
                    @foreach($this->getLinks() as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="relative flex items-center justify-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
                                {!! \Illuminate\Support\Facades\Blade::render($link['iconComponent']) !!}

                                <span class="flex-1 truncate text-sm font-medium {{ $link['labelTextColor'] ?? 'text-gray-900 dark:text-gray-200'}}">
                                    {{ $link['label'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>

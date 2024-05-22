<x-filament::section>
    <x-slot name="heading">
        {{ __('Version') }}
    </x-slot>

    <div class="text-center p-6">
        <x-fas-cube class="h-32 w-32 mx-auto"></x-fas-cube>
        <div class="text-xl font-medium mt-6">
            {{ $instance->version }}
        </div>
    </div>
</x-filament::section>

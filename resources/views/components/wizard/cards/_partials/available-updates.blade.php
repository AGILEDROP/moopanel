@props([
    'coreUpdatesCount' => 0,
    'pluginUpdatesCount' => 0,
    'withText' => true,
])

@php
    $coreColor = $coreUpdatesCount > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500';
    $pluginColor = $pluginUpdatesCount > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500';
@endphp

<div class="mt-6 flex shrink-0 items-center gap-3 flex-wrap sm:flex-nowrap justify-start">
    <div class="relative inline-flex items-center justify-center outline-none gap-1 pointer-events-none">
        <x-fas-cube class="h-4 w-4 {{$coreColor}}" />

        @if($withText)
            <span class="font-medium text-sm {{$coreColor}}" >
                {{ $coreUpdatesCount > 0 ? __('Core updates available') : __('Core up to date') }}
            </span>
        @endif
    </div>

    <div class="relative inline-flex items-center justify-center outline-none gap-1 pointer-events-none">
        <x-fas-plug class="h-4 w-4 {{$pluginColor}}" />

        @if($withText)
            <span class="font-medium text-sm {{$pluginColor}}">
                {{ $pluginUpdatesCount > 0 ? __('Plugin updates available') : __('Plugins up to date') }}
            </span>
        @endif
    </div>
</div>

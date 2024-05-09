@props([
    'record',
])

<div class="searchable-card-label">
    <div class="flex w-full flex-col gap-y-3 py-4">
        <div class="ps-4 pe-4 block w-full">
            <div class="flex-1 w-full">
                <div class="flex flex-col items-start">
                    <img src="{{ $record->image }}" class="h-32 w-32 mb-6 rounded-md ring-white dark:ring-gray-900" alt="Logo">

                    <span class="text-lg text-base text-gray-950 dark:text-white font-semibold searchable-card-label mb-1.5">
                        {{ $record->name }}
                    </span>

                    <span class="text-md leading-6 text-gray-950 dark:text-white">
                        {{ $record->url }}
                    </span>

                    <x-wizard.cards._partials.available-updates
                        :coreUpdatesCount="$record->availableCoreUpdates()->count()"
                        :pluginUpdatesCount="$record->availablePluginUpdates()->count()"
                        :withText="false"
                    ></x-wizard.cards._partials.available-updates>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $wizardTableData = $this->getTableWizardHeaderData();
@endphp

<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$wizardTableData['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        <x-wizard.searchable-records-grid
            searchableRecordClass="searchable-card-label"
            class="lg:grid-cols-2"
        >
            <x-slot:toggable>
                @if($this->getRecords()->count() > 0)
                    <div class="flex shrink-0 items-center gap-x-4">
                        <x-filament::link
                            tag="button"
                            wire:click="toggleAll()"
                        >
                            {{ $this->areAllValuesSelected() ? __('Select all') : __('Deselect all') }}
                        </x-filament::link>
                    </div>
                @endif
            </x-slot:toggable>

            <x-slot:gridItems>
                @foreach($this->getRecords() as $record)
                    <div class="relative h-full bg-white transition duration-75 dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-white/10 rounded-xl shadow-sm dark:bg-white/5
                     @if(!$this->isSelected($record->id)) ring-1 dark:hover:ring-white/20 ring-gray-950/5 dark:ring-white/10 @else ring-primary-500 dark:ring-primary-400 ring-2 @endif"
                         wire:key="{{ $this->getId() }}.records.{{ $record->id }}"
                         wire:click="selectRecord({{$record->id}})"
                    >
                        <x-wizard.cards.cluster
                            :record="$record"
                        ></x-wizard.cards.cluster>
                    </div>
                @endforeach
            </x-slot:gridItems>
        </x-wizard.searchable-records-grid>
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

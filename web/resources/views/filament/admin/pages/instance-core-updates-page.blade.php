<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        <div class="grid grid-cols-1 gap-4 p-6">
            @foreach($this->getRecords() as $record)
                <x-wizard.cards.core-update
                    :record="$record"
                ></x-wizard.cards.core-update>
            @endforeach
        </div>
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
    >
        <div class="mb-6" id="custom-table-square">
            {{ $this->table }}
        </div>

    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

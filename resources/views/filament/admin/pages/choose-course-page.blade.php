<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        @if($this->hasHeaderAction)
            <div class="px-6 pt-6 pb-0 ms-auto">
                {{ $this->redirectToZipUpdatesAction }}
            </div>
        @endif

        {{ $this->table }}
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

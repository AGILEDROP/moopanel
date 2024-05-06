<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        <div class="flex items-center justify-center gap-x-4 px-6 py-12">
            <div class="flex flex-col xl:flex-row gap-10 xl:gap-12">
                @foreach($this->getUpdateTypes() as $updateType)
                    <x-wizard.cards.update-type
                        :type="$updateType['type']"
                        :text="$updateType['text']"
                        :count="$updateType['count']"
                        :selected="$this->updateType === $updateType['type']"
                    ></x-wizard.cards.update-type>
                @endforeach
            </div>
        </div>
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

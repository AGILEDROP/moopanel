<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        <div class="px-6 pt-6 pb-0 ms-auto">
            {{ $this->zipAction }}

            <x-filament-actions::modals />
        </div>
        <div class="flex items-center justify-center gap-x-4 px-6 py-12">

            <!--@todo: maybe it is better to create an action here for zip upload! -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-10 xl:gap-12 mx-auto max-w-full">
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

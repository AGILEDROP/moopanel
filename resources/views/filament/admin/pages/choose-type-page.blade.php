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
        <div class="flex items-center justify-center gap-x-4 px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 xl:gap-12 mx-auto max-w-full @if(count($this->getTypes()) > 2) xl:grid-cols-3 2xl:grid-cols-4 @endif">
                @foreach($this->getTypes() as $type)
                    <x-wizard.cards.type
                        :class="$type['class']"
                        :type="$type['type']"
                        :text="$type['text']"
                        :icon="$type['icon']"
                        :count="$type['count']"
                        :selected="$this->type === $type['type']"
                    ></x-wizard.cards.type>
                @endforeach
            </div>
        </div>
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

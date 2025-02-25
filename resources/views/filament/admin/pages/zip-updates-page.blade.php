<x-filament-panels::page>
    <x-wizard.wizard-wrapper
        :steps="$this->getTableWizardHeaderData()['steps']"
        :currentStep="$currentStep"
        class="md:min-h-[600px]"
    >
        <div class="grid grid-cols-1 gap-4 p-6">
            {{ $this->form }}

            @if(! empty($this->zipResults))
                <div class="mt-4">
                    @foreach($this->zipResults as $instance => $values)
                        <div class="block max-w-full p-6 my-4 bg-white transition duration-75 dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-white/10 rounded-xl shadow-lg dark:bg-white/5 ring-1 dark:hover:ring-white/20 ring-gray-950/5 dark:ring-white/10">
                            <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $instance }}:</h2>
                            @if(!empty($values['results']))
                                <ul class="max-w-full space-y-1 text-gray-500 list-inside dark:text-gray-400  overflow-x-auto">
                                    @foreach($values['results'] as $file => $results)
                                        <li class="flex items-center">
                                            @if($results['status'])
                                                <x-fas-circle-check class="w-3.5 h-3.5 me-2 text-green-500 dark:text-green-400 flex-shrink-0"></x-fas-circle-check>
                                            @else
                                                <x-fas-circle-xmark class="w-3.5 h-3.5 me-2 text-danger-600 dark:text-danger-500 flex-shrink-0"></x-fas-circle-xmark>
                                            @endif
                                            <div>
                                                {{ $file }}:
                                                @if(! $results['status'] && $results['error'])
                                                    <span class="text-danger-600 dark:text-danger-500">{{ $results['error'] }}</span>
                                                @elseif($results['status'] === true)
                                                    <span class="text-green-500 dark:text-green-400">{{ __('Plugin :component was successfully updated.', ['component' => $results['component']]) }}</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-danger-600 dark:text-danger-500">{{ $values['error'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-wizard.wizard-wrapper>
</x-filament-panels::page>

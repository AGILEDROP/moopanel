@props([
    'currentStep' => 1,
    'steps',
])

@php
    $stepsCount = count($steps);
@endphp

<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <ol role="list" class="grid divide-y divide-gray-200 dark:divide-white/5 md:grid-flow-col md:divide-y-0 border-b border-gray-200 dark:border-white/10">
        @foreach($steps as $step)
            <li class="relative flex">
                <a type="button" class="flex h-full w-full items-center gap-x-4 px-6 py-4" aria-current="step" @if($currentStep > $step['step']) href="{{$step['url']}}" @endif>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 @if($currentStep >= $step['step']) border-primary-600 dark:border-primary-500 @else border-gray-300 dark:border-gray-600 @endif" >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full @if($currentStep > $step['step']) bg-primary-600 dark:bg-primary-500 @else border-gray-300 dark:border-gray-600 @endif" >
                            @if($currentStep > $step['step'])
                                <svg  class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                </svg>
                            @endif

                            @if($currentStep <= $step['step'] )
                                <span  class="text-sm font-medium @if($currentStep >= $step['step']) text-primary-600 dark:text-primary-500 @else text-gray-500 dark:text-gray-400 @endif" >
                                {{ $step['step'] }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid justify-items-start">
                    <span class="text-sm font-medium @if($currentStep >= $step['step']) text-primary-600 dark:text-primary-400 @else text-gray-500 dark:text-gray-400 @endif" >
                        {{ $step['name'] }}
                    </span>
                    </div>
                </a>

                @if(! $loop->last)
                    <div aria-hidden="true" class="absolute end-0 hidden h-full w-5 md:block">
                        <svg fill="none" preserveAspectRatio="none" viewBox="0 0 22 80" class="h-full w-full text-gray-200 dark:text-white/5 rtl:rotate-180">
                            <path d="M0 -2L20 40L0 82" stroke-linejoin="round" stroke="currentcolor" vector-effect="non-scaling-stroke"></path>
                        </svg>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>

    <div {{ $attributes->merge(['class' => '']) }}>
        {{ $slot }}
    </div>

    <div class="flex items-center justify-between gap-x-3 px-6 pb-6 border-top-1">
        @if($currentStep > 1)
            <x-filament::button
                color="gray"
                wire:click="goToPreviousStep"
                size="xl"
            >
                {{ __('Back') }}
            </x-filament::button>
        @endif
        @if($currentStep !== $stepsCount)
            <x-filament::button
                color="primary"
                class="ml-auto"
                wire:click="goToNextStep"
                size="xl"
            >
                {{ __('Next step') }}
            </x-filament::button>
        @else
            @if(isset($this->hasUpdateAllAction) && $this->hasUpdateAllAction)
                <x-filament::button
                    color="primary"
                    class="ml-auto"
                    wire:click="updateAll"
                    size="xl"
                >
                    {{ __('Update all') }}
                </x-filament::button>
            @endif
            @if(isset($this->hasBackupAllAction) && $this->hasBackupAllAction)
                <x-filament::button
                    color="primary"
                    class="ml-auto"
                    wire:click="backupAll"
                    size="xl"
                >
                    {{ __('Backup all') }}
                </x-filament::button>
            @endif
        @endif
    </div>
</div>

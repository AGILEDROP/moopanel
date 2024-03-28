<x-filament-panels::page>
    <x-filament::section
        collapsible

        icon="fas-network-wired"
        icon-size="sm"
    >
        <x-slot name="heading">
            {{ __('Connection details') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Configure critical connection settings responsible for communication between our application and moodle instance.') }}
        </x-slot>

        <x-filament-panels::form wire:submit="updateConnectionSettings">
            {{ $this->connectionSettingsForm }}

            <x-filament-panels::form.actions
                :actions="$this->getConnectionSettingsFormActions()"
            />
        </x-filament-panels::form>
    </x-filament::section>


    <x-filament-panels::form wire:submit="updateInstanceData">
        {{ $this->instanceForm }}

        <x-filament-panels::form.actions
            :actions="$this->getInstanceFormActions()"
        />
    </x-filament-panels::form>

    <x-filament::modal
        id="confirm-connection-changes"
        icon="heroicon-o-information-circle"
        icon-color="primary"
        alignment="center"
        width="md"
        :close-button="false"
    >
        <x-slot name="heading">
            {{ __('Are you sure you want to reconnect instance?') }}
        </x-slot>

        <x-slot name="description">
            {{ __('This action will retrieve data from the Moodle instance and may replace existing general settings.') }}
        </x-slot>


        <x-slot name="footerActions">
            <x-filament::button
                wire:click="closeModal('confirm-connection-changes')"
                color="gray"
            >
                {{ __('Close') }}
            </x-filament::button>
            <x-filament::button
                href="https://filamentphp.com"
                color="primary"
                class="ml-auto"
                wire:click="updateConnectionSettings()"
            >
                {{ __('Confirm') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

</x-filament-panels::page>

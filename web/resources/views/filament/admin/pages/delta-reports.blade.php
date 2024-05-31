<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

    @isset($this->diffHtml)
        <div class="flex flex-col overflow-x-auto">
            <div class="inline-block min-w-full">
                <div class="overflow-x-auto p-0.5">
                    {!! $this->diffHtml !!}
                </div>
            </div>
        </div>
    @endisset
</x-filament-panels::page>

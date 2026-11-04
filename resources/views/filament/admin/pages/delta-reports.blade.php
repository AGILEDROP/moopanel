<x-filament-panels::page>
    {{ $this->content }}

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

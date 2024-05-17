@props([
    'type',
    'text',
    'count' => 0,
    'selected' => false,
    'show' => true, //!($type === \App\Enums\UpdateType::CORE_MEGA->value && $count === 0),
    'icon' => \App\Enums\UpdateType::tryFrom($type)->getIconComponent('h-32 w-32 mx-auto text-gray-500 dark:text-gray-300 mb-8'),
])

@if($show)
    <div wire:click="selectUpdateType('{{$type}}')" class="max-w-[340px] h-[340px] relative bg-white transition duration-75 dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-white/10 rounded-xl shadow-lg dark:bg-white/5 p-12 text-center hover:cursor-pointer @if(!$selected) ring-1 dark:hover:ring-white/20 ring-gray-950/5 dark:ring-white/10 @else ring-primary-500 dark:ring-primary-400 ring-2 @endif">
        {!! \Illuminate\Support\Facades\Blade::render($icon) !!}

        <div class="text-xl uppercase font-medium text-gray-500 dark:text-gray-300 ">
            {{ $text }}
        </div>
        <span class="absolute bottom-[2.5rem] left-0 right-0 mx-auto @if($count > 0) text-primary-500 dark:text-primary-400 @else text-gray-500 dark:text-gray-300 @endif">
        {{ $count > 0 ? __(':count updates available', ['count' => $count]) : __('Up to date') }}
    </span>
    </div>
@endif



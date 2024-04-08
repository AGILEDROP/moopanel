@php
    $limit = $getLimit();
    $state = \Illuminate\Support\Arr::wrap($getState());
    $limitedState = array_slice($state, 0, $limit);
    $isStacked = $isStacked();
    $limitedRemainingText = $hasLimitedRemainingText();
@endphp

@if(! $isStacked)
    <div class="mb-4">
        <x-columns.logo-img
            :src="$state[0]"
        ></x-columns.logo-img>
    </div>
@else
    <div class="grid grid-cols-3 grid-flow-col gap-4 mb-4">
        @foreach($limitedState as $logo)
            <x-columns.logo-img
                :src="$logo"
            ></x-columns.logo-img>
        @endforeach
        @if($limitedRemainingText && $loop->iteration < count($state) && (count($state) - count($limitedState) > 0))
            <div class="col-span-1 h-32 w-32 p-4 bg-primary-500 shadow-sm rounded-md content-center text-center">
                <span class="-ms-0.5 text-white text-2xl font-medium">
                    +{{ count($state) - count($limitedState) }}
                </span>
            </div>
        @endif
    </div>
@endif


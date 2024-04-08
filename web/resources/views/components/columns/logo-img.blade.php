@props([
    'src'
])

<div class="col-span-1 h-32 w-32 p-4 bg-primary-500 shadow-sm rounded-md content-center">
    <img src="{{ $src }}"
         class="w-auto ring-white dark:ring-gray-900"
         alt="Logo"
    />
</div>

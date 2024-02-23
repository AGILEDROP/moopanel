<div class="relative flex items-center justify-center text-center">
    <div class="absolute border-t border-gray-200 w-full h-px"></div>
    <p
            class="inline-block relative bg-white text-sm p-2 rounded-full font-medium text-gray-950 dark:bg-gray-800 dark:text-white">
        {{ __('or Login Via') }}
    </p>
</div>

<div class="grid gap-4">
    <a href="/auth/redirect" class="text-gray-950 dark:text-white text-xs font-medium leading-6 text-center">
        <x-fab-microsoft class="h-16 ml-auto mx-auto mb-2 text-gray-400"/>
        Azure Active Directory
    </a>
</div>

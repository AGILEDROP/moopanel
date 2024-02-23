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

@if(session()->exists('social-login-error'))
    <div class="flex items-center p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
        <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
        </svg>
        <span class="sr-only">Error</span>
        <div>
            <span class="font-medium">{{ session()->get('social-login-error') }}</span>
        </div>
    </div>
@endif
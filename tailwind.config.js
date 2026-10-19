import preset from './vendor/filament/filament/tailwind.config.preset';

export default {
    content: [
        './app/Filament/**/*.php',
        './app/helpers.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './resources/views/filament/admin/**/*.blade.php',
        './resources/views/filament/app/**/*.blade.php',
        './storage/framework/views/*.php',
        './vendor/filament/**/*.blade.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    presets: [preset],
};

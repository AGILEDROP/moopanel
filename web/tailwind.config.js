import preset from './vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/**/*.blade.php',
        './resources/views/filament/admin/**/*.blade.php',
        './resources/views/filament/app/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './app/helpers.php',
    ],
}

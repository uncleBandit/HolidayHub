const defaultTheme = require('tailwindcss/defaultTheme');
const forms = require('@tailwindcss/forms');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'deep-ocean': '#01497C',     // dark ocean blue
                'sea-foam': '#A7E9AF',      // soft aqua green
                'tropical-blue': '#0096C7', // bright tropical blue
                'sunset-orange': '#FF5733', // warm sunset orange
                'coral-pink': '#FF6B6B',
                'sandy-beige': '#FFE5B4',
                'sunshine-yellow': '#FFD93D',

            },
        },
    },

    plugins: [forms],
};

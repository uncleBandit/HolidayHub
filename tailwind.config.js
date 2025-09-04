import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
        },

        colors: {
                'deep-ocean': '#01497C',     // dark ocean blue
                'sea-foam': '#A7E9AF',      // soft aqua green
                'tropical-blue': '#0096C7', // bright tropical blue
                'sunset-orange': '#FF5733', // warm sunset orange// example green
            },
    },

    plugins: [forms],
};

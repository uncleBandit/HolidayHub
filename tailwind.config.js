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
            colors: {
                'deep-ocean': '#01497C',
                'sea-foam': '#A7E9AF',
                'tropical-blue': '#0096C7',
                'sunset-orange': '#FF5733',
                'coral-pink': '#FF6B6B',
                'sandy-beige': '#FFE5B4',
                'sunshine-yellow': '#FFD93D',
            },
        },
    },

    plugins: [forms],
};

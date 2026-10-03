import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Modules/*/Resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
                serif: ['Instrument Serif', ...defaultTheme.fontFamily.serif],
                // Discovery design faces
                manrope: ['Manrope', ...defaultTheme.fontFamily.sans],
                'dm-serif': ['DM Serif Display', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Editorial palette (see resources/css/app.css)
                paper: '#F6F4EF',
                ink: '#17211E',
                'ink-soft': '#5D6762',
                line: '#DCDED8',
                coral: '#EF664D',
                'coral-dark': '#D9533C',
                sage: '#DCE4DD',
                'coral-light': '#F5AA94',
                // Discovery palette (Visual Discovery Travel Platform benchmark)
                cream: '#F7F3EB',
                surface: '#FFFDF9',
                sand: '#ECE4D7',
                forest: '#16392F',
                'forest-light': '#275848',
                clay: '#D85F3C',
                'clay-dark': '#B94628',
                muted: '#6F756F',
                // Legacy product palette
                'deep-ocean': '#01497C',
                'sea-foam': '#A7E9AF',
                'tropical-blue': '#0096C7',
                'sunset-orange': '#FF5733',
                'coral-pink': '#FF6B6B',
                'sandy-beige': '#FFE5B4',
                'sunshine-yellow': '#FFD93D',
            },
            borderRadius: {
                '4xl': '2rem',
            },
        },
    },

    plugins: [forms],
};
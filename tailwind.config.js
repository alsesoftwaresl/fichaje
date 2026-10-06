import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta de marca sacada del logo (azul marino #1b1b54, turquesa
                // #2fc6a5). "indigo" se redefine a propósito con el azul marino
                // para que toda la app (botones, enlaces, badges, barra lateral)
                // use la marca sin tocar cada vista; "accent" es el turquesa.
                indigo: {
                    50: '#f1f1fa',
                    100: '#e3e3f4',
                    200: '#c7c7e8',
                    300: '#9d9dd2',
                    400: '#6c6cb3',
                    500: '#3b3b86',
                    600: '#1b1b54',
                    700: '#151545',
                    800: '#101036',
                    900: '#0b0b28',
                    950: '#070719',
                },
                accent: {
                    50: '#ecfdf8',
                    100: '#d0faee',
                    200: '#a3f2de',
                    300: '#6fe4c8',
                    400: '#47d3b3',
                    500: '#2fc6a5',
                    600: '#1fa086',
                    700: '#1b8070',
                    800: '#1a665a',
                    900: '#18544b',
                },
            },
        },
    },

    plugins: [forms],
};

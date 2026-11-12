import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php', // 👉 PASTIKAN BARIS INI ADA
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                page:'#0A0E17',
                surface: '#111827',
                line: '#1F2937',
                accent: { DEFAULT: '#10B981', hover: '#059669', soft: '#34D399' },
                level: {
                    easy: '#10B981',
                    medium: '#F59E0B',
                    hard: '#F97316',
                    insane: '#EF4444',
            },

            }
        },
    },

    plugins: [forms],
};

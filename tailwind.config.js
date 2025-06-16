import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['ui-monospace', 'SFMono-Regular', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
            },
            colors: {
                gray: {
                    750: '#374151',
                    850: '#1f2937',
                    950: '#111827',
                },
                blue: {
                    550: '#3b82f6',
                },
                amber: {
                    550: '#f59e0b',
                },
            },
            animation: {
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'fade-in': 'fadeIn 0.5s ease-in-out',
                'slide-in': 'slideIn 0.3s ease-out',
                'bounce-subtle': 'bounceSubtle 2s infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideIn: {
                    '0%': { transform: 'translateX(100%)' },
                    '100%': { transform: 'translateX(0)' },
                },
                bounceSubtle: {
                    '0%, 20%, 50%, 80%, 100%': { transform: 'translateY(0)' },
                    '40%': { transform: 'translateY(-2px)' },
                    '60%': { transform: 'translateY(-1px)' },
                },
            },
            boxShadow: {
                'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                'soft-lg': '0 10px 40px -10px rgba(0, 0, 0, 0.1)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
                '128': '32rem',
            },
            minHeight: {
                '24': '6rem',
                '32': '8rem',
            },
            maxWidth: {
                '8xl': '88rem',
                '9xl': '96rem',
            },
            zIndex: {
                '60': '60',
                '70': '70',
                '80': '80',
                '90': '90',
                '100': '100',
            },
        },
    },

    plugins: [
        forms,
        // Add custom plugin for utilities
        function({ addUtilities }) {
            const newUtilities = {
                '.text-balance': {
                    'text-wrap': 'balance',
                },
                '.text-pretty': {
                    'text-wrap': 'pretty',
                },
                '.scrollbar-thin': {
                    'scrollbar-width': 'thin',
                },
                '.scrollbar-none': {
                    'scrollbar-width': 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none',
                    },
                },
                '.grid-auto-fit': {
                    'grid-template-columns': 'repeat(auto-fit, minmax(280px, 1fr))',
                },
                '.grid-auto-fill': {
                    'grid-template-columns': 'repeat(auto-fill, minmax(280px, 1fr))',
                },
            }
            addUtilities(newUtilities)
        }
    ],
};
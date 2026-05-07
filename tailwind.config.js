/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ['class', '[data-theme="dark"]'],
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50:  'var(--color-primary-50)',
                    100: 'var(--color-primary-100)',
                    400: 'var(--color-primary-400)',
                    500: 'var(--color-primary-500)',
                    600: 'var(--color-primary-600)',
                    700: 'var(--color-primary-700)',
                    800: 'var(--color-primary-800)',
                    900: 'var(--color-primary-900)',
                },
                surface: {
                    bg:     'var(--surface-bg)',
                    card:   'var(--surface-card)',
                    border: 'var(--surface-border)',
                    input:  'var(--surface-input)',
                },
                success:  'var(--color-success)',
                warning:  'var(--color-warning)',
                danger:   'var(--color-danger)',
                info:     'var(--color-info)',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'monospace'],
            },
            borderRadius: {
                card: '12px',
            },
            boxShadow: {
                card:    '0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04)',
                'card-lg': '0 4px 12px rgba(0,0,0,0.12), 0 2px 4px rgba(0,0,0,0.06)',
            },
        },
    },
    plugins: [],
};

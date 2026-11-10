import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand system aligned with Figma
                brand: {
                    dark: '#374746', // deep muted green
                    accent: '#99745C', // warm brown accent
                },
                neutral: {
                    bg: '#EFF3F3', // page background from design
                    surface: '#F5F5F6', // light card/background
                    text: '#242528', // primary text
                    muted: '#7E7E7E', // secondary body text
                },
                border: {
                    neutral: '#CED0D3',
                },
                primary: 'var(--btn-primary-bg)',
                secondary: 'var(--btn-secondary-bg)',
                warning: 'var(--warning)',
                success: 'var(--success)',
                alert: 'var(--danger)',
                link: 'var(--text-link)',
                linkHover: 'var(--text-link-hover)',
            },
            borderRadius: {
                pill: '9999px',
                xl2: '1.25rem',
            },
        },
    },
    plugins: [],
};

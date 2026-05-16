/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', 'system-ui', 'sans-serif'],
                mono: ['IBM Plex Mono', 'monospace'],
            },
            colors: {
                bg: '#eef0f2',
                'bg-panel': '#ffffff',
                'bg-header': '#1e2530',
                'bg-soft': '#f6f7f9',
                'bg-row-alt': '#fafbfc',
                ink: '#1e2530',
                'ink-soft': '#4a5260',
                'ink-mute': '#8a92a0',
                line: '#d5d9e0',
                'line-soft': '#e6e9ee',
                'line-strong': '#b8bec8',
                accent: '#1e5fa8',
                'accent-soft': '#e3edf7',
                warn: '#b8620b',
                'warn-soft': '#fbf0df',
                danger: '#b8302a',
                'danger-soft': '#fbe5e3',
                ok: '#2d7a3d',
                'ok-soft': '#e3f0e6',
                'aler-gluten': '#b8302a',
                'aler-lactosa': '#1e5fa8',
                'aler-huevo': '#b8620b',
                'aler-frutos': '#6b4a8a',
            },
        },
    },
    plugins: [],
};

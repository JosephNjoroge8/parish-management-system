export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {
            // Fix vendor prefix issues
            overrideBrowserslist: [
                'defaults',
                'not IE 11',
                'not IE_Mob 11'
            ],
            // Ensure proper vendor prefixes
            flexbox: 'no-2009',
            grid: true,
        },
    },
};

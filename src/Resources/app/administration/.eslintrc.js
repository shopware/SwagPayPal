module.exports = {
    rules: {
        'sw-deprecation-rules/private-feature-declarations': 'off',
    },
    overrides: [
        {
            files: ['**/*.js'],
            rules: {
                'sw-deprecation-rules/private-feature-declarations': 'off',
            },
        },
    ],
};

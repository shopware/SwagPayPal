const { join } = require('path');

// use ADMIN_PATH environment variable to change from default installation
process.env.ADMIN_PATH =
    process.env.ADMIN_PATH ??
    join(__dirname, '../../../../../../../src/Administration/Resources/app/administration/src');

module.exports = {
    extends: '@shopware-ag/eslint-config-base',
    env: {
        browser: true,
        'jest/globals': true,
    },

    globals: {
        Shopware: true,
        wrapTestComponent: true,
    },

    plugins: [
        'jest',
        'internal-rules',
    ],

    settings: {
        'import/resolver': {
            node: {},
            webpack: {
                config: {
                    // Sync with webpack.config.js
                    resolve: {
                        extensions: ['.js', '.ts', '.vue', '.json', '.less', '.twig'],
                        alias: {
                            SwagPayPal: join(__dirname, 'src'),
                            src: process.env.ADMIN_PATH,
                            '@vue\/test-utils': `${process.env.ADMIN_PATH}/node_modules/@vue/test-utils`,
                        },
                    },
                },
            },
        },
    },

    rules: {
        'import/no-useless-path-segments': 0,
        'import/extensions': ['error', 'always', {
            js: 'never',
            ts: 'never',
            vue: 'never',
        }],
        'no-console': ['error', { allow: ['warn', 'error'] }],
        'comma-dangle': ['error', 'always-multiline'],
        'internal-rules/no-src-imports': 'error',
        'import/no-extraneous-dependencies': ['error', { optionalDependencies: ['src/**/*.spec.[t|j]s'] }],
    },

    overrides: [{
        files: 'src/**/*.spec.[t|j]s',
        rules: {
            // lets depend on shopware's deps
            'import/no-extraneous-dependencies': 'off',
        },
    }],
};

const { join } = require('path');

// use ADMIN_PATH environment variable to change from default installation
process.env.ADMIN_PATH =
    process.env.ADMIN_PATH ??
    join(__dirname, '../../../../../../../src/Administration/Resources/app/administration/src');

const baseRules = {
    'max-len': 0,
    'import/no-useless-path-segments': 0,
    'import/extensions': ['error', 'always', {
        js: 'never',
        ts: 'never',
        vue: 'never',
    }],
    'no-console': ['error', { allow: ['warn', 'error'] }],
    'comma-dangle': ['error', 'always-multiline'],
    'internal-rules/no-src-imports': 0,
    // lets depend on shopware's deps (vue and @vue/test-utils)
    'import/no-extraneous-dependencies': 'off',
};

module.exports = {
    extends: [
        '@shopware-ag/eslint-config-base',
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended',
    ],

    env: {
        browser: true,
        'jest/globals': true,
    },

    globals: {
        Shopware: true,
        wrapTestComponent: true,
        flushPromises: true,
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
                            vue: `${process.env.ADMIN_PATH}/node_modules/@vue/compat/dist/vue.cjs.js`,
                        },
                    },
                },
            },
        },
    },

    rules: baseRules,

    overrides: [{
        files: ['**/*.ts', '**/*.tsx'],
        extends: [
            '@shopware-ag/eslint-config-base',
            'plugin:@typescript-eslint/eslint-recommended',
            'plugin:@typescript-eslint/recommended',
            'plugin:@typescript-eslint/recommended-requiring-type-checking',
        ],
        parser: '@typescript-eslint/parser',
        parserOptions: {
            tsconfigRootDir: __dirname,
            project: ['./tsconfig.json'],
        },
        plugins: ['@typescript-eslint'],
        rules: {
            ...baseRules,
            indent: 'off',
            'no-void': 'off',
            'no-unused-vars': 'off',
            'no-shadow': 'off',
            'import/extensions': [
                'error',
                'ignorePackages',
                {
                    js: 'never',
                    jsx: 'never',
                    ts: 'never',
                    tsx: 'never',
                },
            ],

            '@typescript-eslint/indent': ['error', 4, { SwitchCase: 1 }],
            '@typescript-eslint/ban-ts-comment': 0,
            '@typescript-eslint/no-unsafe-member-access': 'error',
            '@typescript-eslint/no-unsafe-call': 'error',
            '@typescript-eslint/no-unsafe-assignment': 'error',
            '@typescript-eslint/no-unsafe-return': 'error',
            '@typescript-eslint/explicit-module-boundary-types': 0,
            '@typescript-eslint/prefer-ts-expect-error': 'error',
            '@typescript-eslint/no-floating-promises': 0,
            '@typescript-eslint/no-shadow': 'error',
            '@typescript-eslint/consistent-type-imports': 'error',
            '@typescript-eslint/no-unused-vars': ['error', {
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_',
                caughtErrorsIgnorePattern: '^_',
            }],
            '@typescript-eslint/no-namespace': 'off',
            '@typescript-eslint/restrict-template-expressions': 'off',
            '@typescript-eslint/member-delimiter-style': 'error',
        },
    }],
};

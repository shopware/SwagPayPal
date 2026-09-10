import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import importPlugin from 'eslint-plugin-import';
import stylistic from '@stylistic/eslint-plugin';

export default tseslint.config(
    eslint.configs.recommended,
    tseslint.configs.recommended,
    {
        files: ['**/*.ts', '**/*.js'],
        ignores: ['**/*.d.ts'],

        plugins: {
            import: importPlugin,
            stylistic,
        },

        extends: [
            ...tseslint.configs.recommendedTypeCheckedOnly,
        ],

        languageOptions: {
            ecmaVersion: 'latest',
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },

        settings: {
            'import/resolver': {
                node: {},
                typescript: {
                    project: './tsconfig.json',
                },
            },
        },

        rules: {
            'no-console': ['error', { allow: ['warn', 'error'] }],

            /* import rules */
            'import/no-extraneous-dependencies': 'off',
            'import/no-useless-path-segments': 'off',
            'import/extensions': [
                'error',
                'ignorePackages',
                { js: 'never', ts: 'never' },
            ],
            /* import rules */

            /* stylistic rules */
            'stylistic/semi': ['error', 'always'],
            'stylistic/linebreak-style': ['error', 'unix'],
            'stylistic/no-multiple-empty-lines': ['error', { max: 2, maxEOF: 1 }],
            'stylistic/indent': ['error', 4, { SwitchCase: 1 }],
            'stylistic/member-delimiter-style': ['error'],
            'stylistic/no-multi-spaces': ['error'],
            'stylistic/object-curly-spacing': ['error', 'always'],
            'stylistic/space-before-function-paren': ['error', {
                anonymous: 'always',
                named: 'never',
                asyncArrow: 'always',
            }],
            'stylistic/spaced-comment': ['error', 'always'],
            'stylistic/no-tabs': ['error'],
            'stylistic/no-mixed-spaces-and-tabs': ['error'],
            'stylistic/max-len': 'off',
            'stylistic/quote-props': ['error', 'as-needed'],
            'stylistic/no-extra-semi': ['error'],
            'stylistic/comma-dangle': ['error', 'always-multiline'],
            /* stylistic rules */

            /* typescript rules */
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/ban-ts-comment': ['error', { 'ts-expect-error': false }],
            '@typescript-eslint/no-unsafe-member-access': ['error', { allowOptionalChaining: true }],
            '@typescript-eslint/no-unsafe-call': 'error',
            '@typescript-eslint/no-unsafe-assignment': 'error',
            '@typescript-eslint/no-unsafe-return': 'error',
            '@typescript-eslint/no-unsafe-argument': 'error',
            '@typescript-eslint/explicit-module-boundary-types': 'off',
            '@typescript-eslint/prefer-ts-expect-error': 'error',
            '@typescript-eslint/no-floating-promises': 'off',
            '@typescript-eslint/no-shadow': 'error',
            '@typescript-eslint/consistent-type-imports': 'error',
            '@typescript-eslint/no-unused-vars': 'off',
            '@typescript-eslint/no-namespace': 'off',
            '@typescript-eslint/restrict-template-expressions': 'off',
            '@typescript-eslint/no-empty-object-type': ['error', { allowInterfaces: 'always' }],
            '@typescript-eslint/no-redundant-type-constituents': 'off',
            /* typescript rules */
        },
    },
    {
        files: ['**/*.js'],

        extends: [tseslint.configs.disableTypeChecked],
    },
);

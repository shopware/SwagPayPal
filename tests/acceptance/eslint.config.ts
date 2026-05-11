import tseslint from 'typescript-eslint';
import eslint from '@eslint/js';
import playwright from 'eslint-plugin-playwright';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';
import { defineConfig } from 'eslint/config';

export default defineConfig(
    eslint.configs.recommended,
    tseslint.configs.recommendedTypeChecked,
    playwright.configs['flat/recommended'],
    stylistic.configs['recommended'],
    {
        languageOptions: {
            ecmaVersion: 'latest',
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
            globals: globals.node,
        },

        plugins: { '@stylistic': stylistic },

        rules: {
            /* stylistic rules */
            '@stylistic/semi': ['error', 'always'],
            '@stylistic/indent': ['error', 4, { SwitchCase: 1 }],
            '@stylistic/member-delimiter-style': ['error'],
            '@stylistic/no-multi-spaces': ['error'],
            '@stylistic/object-curly-spacing': ['error', 'always'],
            '@stylistic/space-before-function-paren': ['error', {
                anonymous: 'always',
                named: 'never',
                asyncArrow: 'always',
            }],
            '@stylistic/spaced-comment': ['error', 'always'],
            '@stylistic/no-tabs': ['error'],
            '@stylistic/no-mixed-spaces-and-tabs': ['error'],
            '@stylistic/max-len': 'off',
            '@stylistic/quote-props': ['error', 'as-needed'],
            '@stylistic/no-extra-semi': ['error'],
            '@stylistic/comma-dangle': ['error', 'always-multiline'],
            /* stylistic rules */

            /* typescript rules */
            '@typescript-eslint/no-unused-vars': 'warn',
            /* typescript rules */

            /* playwright rules */
            'playwright/expect-expect': 'off',
            /* playwright rules */
        },
    },
);

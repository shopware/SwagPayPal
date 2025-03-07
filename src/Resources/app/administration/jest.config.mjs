import { resolve } from 'path';

process.env.ADMIN_PATH =
    process.env.ADMIN_PATH || resolve('../../../../../../../src/Administration/Resources/app/administration');

export default {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        // required, e.g. /www/sw6/platform/src/Administration/Resources/app/administration
        adminPath: process.env.ADMIN_PATH,
    },

    testMatch: [
        '<rootDir>/**/*.spec.js',
        '<rootDir>/**/*.spec.ts',
    ],

    setupFilesAfterEnv: [
        `${process.env.ADMIN_PATH}/test/_setup/prepare_environment.js`,
        '<rootDir>/jest.setup.js',
    ],

    collectCoverageFrom: [
        '<rootDir>/src/**/*.js',
        '<rootDir>/src/**/*.ts',
        '!<rootDir>/src/**/*.spec.js',
        '!<rootDir>/src/**/*.spec.ts',
    ],

    transform: {
        '.*\\.svg': `${process.env.ADMIN_PATH}/test/transformer/svgStringifyTransformer.js`,
    },

    transformIgnorePatterns: [
        '/node_modules/(?!(@shopware-ag/meteor-component-library|@shopware-ag/meteor-icon-kit|uuidv7)/)',
    ],

    moduleNameMapper: {
        '^SwagPayPal(.*)$': '<rootDir>/src$1',
        '^SwagPayPal/static/(.*)$': '<rootDir>/static/$1',
        '^src(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        '^@shopware-ag/meteor-admin-sdk/es/(.*)': `${process.env.ADMIN_PATH}/node_modules/@shopware-ag/meteor-admin-sdk/umd/$1`,
        '^@shopware-ag/meteor-component-library$': `${process.env.ADMIN_PATH}/node_modules/@shopware-ag/meteor-component-library/dist/common/index.js`,
        vue$: `${process.env.ADMIN_PATH}/node_modules/vue/dist/vue.cjs.js`,
        '^@vue/test-utils$': `${process.env.ADMIN_PATH}/node_modules/@vue/test-utils/dist/vue-test-utils.cjs.js`,
    },

    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};

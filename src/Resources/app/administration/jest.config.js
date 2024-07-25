const { resolve, join } = require('path');

process.env.ADMIN_PATH =
    process.env.ADMIN_PATH || resolve('../../../../../../../src/Administration/Resources/app/administration');

module.exports = {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        // required, e.g. /www/sw6/platform/src/Administration/Resources/app/administration
        adminPath: process.env.ADMIN_PATH,
    },

    testMatch: [
        '<rootDir>/**/*.spec.js',
    ],

    setupFilesAfterEnv: [
        resolve(join(process.env.ADMIN_PATH, '/test/_setup/prepare_environment.js')),
        '<rootDir>/jest.setup.js',
    ],

    collectCoverageFrom: [
        '<rootDir>/src/**/*.js',
        '<rootDir>/src/**/*.ts',
        '!<rootDir>/src/**/*.spec.js',
        '!<rootDir>/src/**/*.spec.ts',
    ],

    transform: {
        // stringify svg imports
        '.*\\.(svg)$': `${process.env.ADMIN_PATH}/test/transformer/svgStringifyTransformer.js`,
    },

    transformIgnorePatterns: [
        '/node_modules/(?!(@shopware-ag/meteor-icon-kit|uuidv7|other)/)',
    ],

    moduleNameMapper: {
        '^SwagPayPal(.*)$': '<rootDir>/src$1',
        '^src(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        '^\@shopware-ag\/meteor-admin-sdk\/es\/(.*)':
            `${process.env.ADMIN_PATH}/node_modules/@shopware-ag/meteor-admin-sdk/umd/$1`,
        'vue$': `${process.env.ADMIN_PATH}/node_modules/@vue/compat/dist/vue.cjs.js`,
        '^@vue\/test-utils$': `${process.env.ADMIN_PATH}/node_modules/@vue/test-utils/dist/vue-test-utils.cjs.js`,
    },

    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};

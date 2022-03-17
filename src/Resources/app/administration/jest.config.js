const { join, resolve } = require('path');

const ADMIN_PATH = process.env.ADMIN_PATH || resolve('../../../../../../../src/Administration/Resources/app/administration');

module.exports = {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        // required, e.g. /www/sw6/platform/src/Administration/Resources/app/administration
        adminPath: ADMIN_PATH,
    },

    setupFilesAfterEnv: [
        resolve(join(ADMIN_PATH, '/test/_setup/prepare_environment.js')),
    ],

    moduleNameMapper: {
        '^test(.*)$': '<rootDir>/test$1',
        vue$: 'vue/dist/vue.common.dev.js',
    },
};

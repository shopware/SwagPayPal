import { Module } from 'src/core/shopware';
import paypalSettings from './page/swag-paypal';
import './extension/sw-settings-index';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('swag-paypal', {
    type: 'plugin',
    name: 'SwagPayPal',
    description: 'swag-paypal.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: paypalSettings,
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    navigation: [{
        id: 'swag-paypal',
        parent: 'sw-settings',
        label: 'swag-paypal.general.mainMenuItemGeneral',
        color: '#0070ba',
        path: 'swag.paypal.index',
        icon: 'default-money-cash',
        position: 1010
    }]
});

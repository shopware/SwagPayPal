import paypalSettings from './page/swag-paypal';
import './extension/sw-settings-index';
import './components/sw-paypal-credentials';
import './components/sw-paypal-express';
import './components/sw-paypal-behaviour';
import './components/sw-paypal-spb';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('swag-paypal', {
    type: 'plugin',
    name: 'SwagPayPal',
    title: 'swag-paypal.general.mainMenuItemGeneral',
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
    }
});

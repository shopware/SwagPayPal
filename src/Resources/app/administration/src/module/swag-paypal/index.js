import './page/swag-paypal';
import './extension/sw-plugin';
import './components/swag-paypal-behaviour';
import './components/swag-paypal-credentials';
import './components/swag-paypal-express';
import './components/swag-paypal-installment';
import './components/swag-paypal-plus';
import './components/swag-paypal-settings-icon';
import './components/swag-paypal-spb';
import './components/swag-paypal-locale-field';
import './components/sw-paypal-behaviour';
import './components/sw-paypal-credentials';
import './components/sw-paypal-express';
import './components/sw-paypal-installment';
import './components/sw-paypal-plus';
import './components/sw-paypal-spb';
import './components/sw-paypal-locale-field';

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
            component: 'swag-paypal',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    settingsItem: {
        // TODO PPI-159 - Add privilege
        group: 'plugins',
        to: 'swag.paypal.index',
        iconComponent: 'swag-paypal-settings-icon',
        backgroundEnabled: true
    }
});

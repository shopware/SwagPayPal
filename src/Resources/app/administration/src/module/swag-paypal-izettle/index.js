import './components/swag-paypal-izettle-status';
import './components/swag-paypal-izettle-wizard';

import './extension/sw-sales-channel-menu';
import './extension/sw-sales-channel-modal';

import './page/swag-paypal-izettle';

import './view/swag-paypal-izettle-detail-base';
import './view/swag-paypal-izettle-detail-settings';
import './view/swag-paypal-izettle-detail-log';

import './view/swag-paypal-izettle-wizard-connection';
import './view/swag-paypal-izettle-wizard-saleschannel';
import './view/swag-paypal-izettle-wizard-locale';
import './view/swag-paypal-izettle-wizard-productstream';
import './view/swag-paypal-izettle-wizard-sync';
import './view/swag-paypal-izettle-wizard-finish';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('swag-paypal-izettle', {
    type: 'plugin',
    name: 'SwagPayPalIZettle',
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
        detail: {
            component: 'swag-paypal-izettle',
            path: 'detail/:id',
            redirect: {
                name: 'swag.paypal.izettle.detail.base'
            },
            children: {
                base: {
                    component: 'swag-paypal-izettle-detail-base',
                    path: 'base'
                },
                settings: {
                    component: 'swag-paypal-izettle-detail-settings',
                    path: 'settings'
                },
                log: {
                    component: 'swag-paypal-izettle-detail-log',
                    path: 'log'
                }
            }
        },
        wizard: {
            component: 'swag-paypal-izettle',
            path: 'wizard',
            redirect: {
                name: 'swag.paypal.izettle.wizard.connection'
            },
            children: {
                connection: {
                    component: 'swag-paypal-izettle-wizard-connection',
                    path: 'connection'
                },
                'sales-channel': {
                    component: 'swag-paypal-izettle-wizard-saleschannel',
                    path: 'sales-channel'
                },
                locale: {
                    component: 'swag-paypal-izettle-wizard-locale',
                    path: 'locale'
                },
                'product-stream': {
                    component: 'swag-paypal-izettle-wizard-productstream',
                    path: 'product-stream'
                },
                sync: {
                    component: 'swag-paypal-izettle-wizard-sync',
                    path: 'sync'
                },
                finish: {
                    component: 'swag-paypal-izettle-wizard-finish',
                    path: 'finish'
                }
            }
        }
    }
});

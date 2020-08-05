import './component';
import './extension';
import './page';

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

    routes: {
        detail: {
            component: 'swag-paypal-izettle',
            path: ':id/detail',
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
            component: 'swag-paypal-izettle-wizard',
            path: ':id?/wizard',
            redirect: {
                name: 'swag.paypal.izettle.wizard.connection'
            },
            children: {
                connection: {
                    component: 'swag-paypal-izettle-wizard-connection',
                    path: 'connection'
                },
                'connection-success': {
                    component: 'swag-paypal-izettle-wizard-connection-success',
                    path: 'connection-success'
                },
                customization: {
                    component: 'swag-paypal-izettle-wizard-customization',
                    path: 'customization'
                },
                'product-selection': {
                    component: 'swag-paypal-izettle-wizard-product-selection',
                    path: 'product-selection'
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

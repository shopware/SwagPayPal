import './component';
import './extension';
import './page';

const { Module } = Shopware;

Module.register('swag-paypal-pos', {
    type: 'plugin',
    name: 'SwagPayPalPos',
    title: 'swag-paypal.general.mainMenuItemGeneral',
    description: 'swag-paypal.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    routes: {
        detail: {
            component: 'swag-paypal-pos',
            path: ':id/detail',
            redirect: {
                name: 'swag.paypal.pos.detail.overview'
            },
            children: {
                overview: {
                    component: 'swag-paypal-pos-detail-overview',
                    path: 'overview'
                },
                syncedProducts: {
                    component: 'swag-paypal-pos-detail-synced-products',
                    path: 'synced-products'
                },
                settings: {
                    component: 'swag-paypal-pos-detail-settings',
                    path: 'settings'
                },
                runs: {
                    component: 'swag-paypal-pos-detail-runs',
                    path: 'runs'
                }
            }
        },
        wizard: {
            component: 'swag-paypal-pos-wizard',
            path: ':id?/wizard',
            redirect: {
                name: 'swag.paypal.pos.wizard.connection'
            },
            children: {
                connection: {
                    component: 'swag-paypal-pos-wizard-connection',
                    path: 'connection'
                },
                connectionSuccess: {
                    component: 'swag-paypal-pos-wizard-connection-success',
                    path: 'connection-success'
                },
                connectionDisconnect: {
                    component: 'swag-paypal-pos-wizard-connection-disconnect',
                    path: 'connection-disconnect'
                },
                customization: {
                    component: 'swag-paypal-pos-wizard-customization',
                    path: 'customization'
                },
                productSelection: {
                    component: 'swag-paypal-pos-wizard-product-selection',
                    path: 'product-selection'
                },
                syncPrices: {
                    component: 'swag-paypal-pos-wizard-sync-prices',
                    path: 'sync-prices'
                },
                syncLibrary: {
                    component: 'swag-paypal-pos-wizard-sync-library',
                    path: 'sync-library'
                },
                finish: {
                    component: 'swag-paypal-pos-wizard-finish',
                    path: 'finish'
                }
            }
        }
    }
});

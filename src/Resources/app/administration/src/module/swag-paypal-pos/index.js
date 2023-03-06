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
    icon: 'regular-sync',

    routes: {
        detail: {
            component: 'swag-paypal-pos',
            path: ':id/detail',
            redirect: {
                name: 'swag.paypal.pos.detail.overview',
            },
            meta: {
                privilege: 'sales_channel.viewer',
            },
            children: {
                overview: {
                    component: 'swag-paypal-pos-detail-overview',
                    path: 'overview',
                    meta: {
                        privilege: 'sales_channel.viewer',
                    },
                },
                syncedProducts: {
                    component: 'swag-paypal-pos-detail-synced-products',
                    path: 'synced-products',
                    meta: {
                        privilege: 'sales_channel.viewer',
                    },
                },
                settings: {
                    component: 'swag-paypal-pos-detail-settings',
                    path: 'settings',
                    meta: {
                        privilege: 'sales_channel.viewer',
                    },
                },
                runs: {
                    component: 'swag-paypal-pos-detail-runs',
                    path: 'runs',
                    meta: {
                        privilege: 'sales_channel.viewer',
                    },
                },
            },
        },
        wizard: {
            component: 'swag-paypal-pos-wizard',
            path: ':id?/wizard',
            redirect: {
                name: 'swag.paypal.pos.wizard.connection',
            },
            meta: {
                privilege: 'sales_channel.creator',
            },
            children: {
                connection: {
                    component: 'swag-paypal-pos-wizard-connection',
                    path: 'connection',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                connectionSuccess: {
                    component: 'swag-paypal-pos-wizard-connection-success',
                    path: 'connection-success',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                connectionDisconnect: {
                    component: 'swag-paypal-pos-wizard-connection-disconnect',
                    path: 'connection-disconnect',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                customization: {
                    component: 'swag-paypal-pos-wizard-customization',
                    path: 'customization',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                productSelection: {
                    component: 'swag-paypal-pos-wizard-product-selection',
                    path: 'product-selection',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                syncPrices: {
                    component: 'swag-paypal-pos-wizard-sync-prices',
                    path: 'sync-prices',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                syncLibrary: {
                    component: 'swag-paypal-pos-wizard-sync-library',
                    path: 'sync-library',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
                finish: {
                    component: 'swag-paypal-pos-wizard-finish',
                    path: 'finish',
                    meta: {
                        privilege: 'sales_channel.creator',
                    },
                },
            },
        },
    },
});

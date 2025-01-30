Shopware.Component.register('swag-paypal-settings-icon', () => import('./components/swag-paypal-settings-icon'));
Shopware.Component.register('swag-paypal-settings-locale-select', () => import('./components/swag-paypal-settings-locale-select'));
Shopware.Component.register('swag-paypal-settings-sales-channel-switch', () => import('./components/swag-paypal-settings-sales-channel-switch'));
Shopware.Component.register('swag-paypal-settings-webhook', () => import('./components/swag-paypal-settings-webhook'));

Shopware.Component.register('swag-paypal-settings-advanced', () => import('./view/swag-paypal-settings-advanced'));
Shopware.Component.register('swag-paypal-settings-general', () => import('./view/swag-paypal-settings-general'));
Shopware.Component.register('swag-paypal-settings-storefront', () => import('./view/swag-paypal-settings-storefront'));

Shopware.Component.register('swag-paypal-settings', () => import('./page/swag-paypal-settings'));

Shopware.Module.register('swag-paypal-settings', {
    type: 'plugin',
    name: 'SwagPayPalSettings',
    title: 'swag-paypal-settings.module.title',
    description: 'swag-paypal-settings.module.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',

    routes: {
        index: {
            component: 'swag-paypal-settings',
            path: 'index',
            redirect: { name: 'swag.paypal.settings.index.general' },
            meta: {
                parentPath: 'sw.settings.index.plugins',
                privilege: 'swag_paypal.viewer',
            },
            children: {
                general: {
                    path: 'general',
                    component: 'swag-paypal-settings-general',
                    meta: {
                        privilege: 'swag_paypal.viewer',
                        parentPath: 'sw.settings.index',
                    },
                },
                storefront: {
                    path: 'storefront',
                    component: 'swag-paypal-settings-storefront',
                    meta: {
                        privilege: 'swag_paypal.viewer',
                        parentPath: 'sw.settings.index',
                    },
                },
                advanced: {
                    path: 'advanced',
                    component: 'swag-paypal-settings-advanced',
                    meta: {
                        privilege: 'swag_paypal.viewer',
                        parentPath: 'sw.settings.index',
                    },
                },
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'swag.paypal.settings.index',
        iconComponent: 'swag-paypal-settings-icon',
        backgroundEnabled: true,
        privilege: 'swag_paypal.viewer',
    },
});

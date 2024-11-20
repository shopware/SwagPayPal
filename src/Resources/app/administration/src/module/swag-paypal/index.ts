import './acl';

Shopware.Component.extend('swag-paypal-locale-field', 'sw-text-field', () => import('./components/swag-paypal-locale-field'));
Shopware.Component.extend('swag-paypal-plugin-box-with-onboarding', 'sw-plugin-box', () => import('./components/swag-paypal-plugin-box-with-onboarding'));
Shopware.Component.register('swag-paypal-acdc', () => import('./components/swag-paypal-acdc'));
Shopware.Component.register('swag-paypal-behavior', () => import('./components/swag-paypal-behavior'));
Shopware.Component.register('swag-paypal-checkout', () => import('./components/swag-paypal-checkout'));
Shopware.Component.register('swag-paypal-checkout-method', () => import('./components/swag-paypal-checkout-method'));
Shopware.Component.register('swag-paypal-checkout-domain-association', () => import('./components/swag-paypal-checkout-domain-association'));
Shopware.Component.register('swag-paypal-created-component-helper', () => import('./components/swag-paypal-created-component-helper'));
Shopware.Component.register('swag-paypal-credentials', () => import('./components/swag-paypal-credentials'));
Shopware.Component.register('swag-paypal-cross-border', () => import('./components/swag-paypal-cross-border'));
Shopware.Component.register('swag-paypal-express', () => import('./components/swag-paypal-express'));
Shopware.Component.register('swag-paypal-inherit-wrapper', () => import('./components/swag-paypal-inherit-wrapper'));
Shopware.Component.register('swag-paypal-installment', () => import('./components/swag-paypal-installment'));
Shopware.Component.register('swag-paypal-plus', () => import('./components/swag-paypal-plus'));
Shopware.Component.register('swag-paypal-pui', () => import('./components/swag-paypal-pui'));
Shopware.Component.register('swag-paypal-settings-hint', () => import('./components/swag-paypal-settings-hint'));
Shopware.Component.register('swag-paypal-settings-icon', () => import('./components/swag-paypal-settings-icon'));
Shopware.Component.register('swag-paypal-spb', () => import('./components/swag-paypal-spb'));
Shopware.Component.register('swag-paypal-vaulting', () => import('./components/swag-paypal-vaulting'));
Shopware.Component.register('swag-paypal-webhook', () => import('./components/swag-paypal-webhook'));
Shopware.Component.register('swag-paypal', () => import('./page/swag-paypal'));

const { Module } = Shopware;

Module.register('swag-paypal', {
    type: 'plugin',
    name: 'SwagPayPal',
    title: 'swag-paypal.general.mainMenuItemGeneral',
    description: 'swag-paypal.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',

    routes: {
        index: {
            component: 'swag-paypal',
            path: ':tab?',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'swag_paypal.viewer',
            },
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'swag.paypal.index',
        iconComponent: 'swag-paypal-settings-icon',
        backgroundEnabled: true,
        privilege: 'swag_paypal.viewer',
    },
});

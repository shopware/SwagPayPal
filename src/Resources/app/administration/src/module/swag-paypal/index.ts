import './acl';
import './page/swag-paypal';
import './components/swag-paypal-settings-hint';
import './components/swag-paypal-acdc';
import './components/swag-paypal-behavior';
import './components/swag-paypal-checkout';
import './components/swag-paypal-checkout-method';
import './components/swag-paypal-checkout-domain-association';
import './components/swag-paypal-created-component-helper';
import './components/swag-paypal-credentials';
import './components/swag-paypal-cross-border';
import './components/swag-paypal-express';
import './components/swag-paypal-installment';
import './components/swag-paypal-plus';
import './components/swag-paypal-pui';
import './components/swag-paypal-settings-icon';
import './components/swag-paypal-spb';
import './components/swag-paypal-plugin-box-with-onboarding';
import './components/swag-paypal-locale-field';
import './components/swag-paypal-vaulting';
import './components/swag-paypal-webhook';

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

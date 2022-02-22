import './acl';
import './page/swag-paypal';
import './components/swag-paypal-settings-hint';
import './components/swag-paypal-behavior';
import './components/swag-paypal-checkout';
import './components/swag-paypal-credentials';
import './components/swag-paypal-express';
import './components/swag-paypal-installment';
import './components/swag-paypal-plus';
import './components/swag-paypal-settings-icon';
import './components/swag-paypal-spb';
import './components/swag-paypal-plugin-box-with-onboarding';
import './components/swag-paypal-locale-field';

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

    routes: {
        index: {
            component: 'swag-paypal',
            path: 'index',
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

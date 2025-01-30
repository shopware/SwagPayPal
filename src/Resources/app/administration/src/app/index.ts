import './acl';
import './store/swag-paypal-merchant-information.store';
import './store/swag-paypal-settings.store';

Shopware.Component.register('swag-paypal-onboarding-button', () => import('./component/swag-paypal-onboarding-button'));
Shopware.Component.register('swag-paypal-setting', () => import('./component/swag-paypal-setting'));

// synchronise salesChannel of stores
Shopware.Vue.watch(
    () => Shopware.Store.get('swagPayPalSettings').salesChannel,
    (salesChannel) => { Shopware.Store.get('swagPayPalMerchantInformation').salesChannel = salesChannel; },
);

Shopware.Vue.watch(
    () => Shopware.Store.get('swagPayPalMerchantInformation').salesChannel,
    (salesChannel) => { Shopware.Store.get('swagPayPalSettings').salesChannel = salesChannel; },
);

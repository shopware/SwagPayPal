import './store/swag-paypal-merchant-information.store';
import './store/swag-paypal-settings.store';

// synchronise salesChannel of stores
Shopware.Vue.watch(
    () => Shopware.Store.get('swagPayPalSettings').salesChannel,
    (salesChannel) => { Shopware.Store.get('swagPayPalMerchantInformation').salesChannel = salesChannel; },
);

Shopware.Vue.watch(
    () => Shopware.Store.get('swagPayPalMerchantInformation').salesChannel,
    (salesChannel) => { Shopware.Store.get('swagPayPalSettings').salesChannel = salesChannel; },
);

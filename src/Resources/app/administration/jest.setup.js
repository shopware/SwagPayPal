import 'SwagPayPal/mixin/swag-paypal-credentials-loader.mixin';
import 'SwagPayPal/mixin/swag-paypal-notification.mixin';
import 'SwagPayPal/mixin/swag-paypal-pos-catch-error.mixin';
import 'SwagPayPal/mixin/swag-paypal-pos-log-label.mixin';

import 'SwagPayPal/mixin/swag-paypal-settings.mixin';
import 'SwagPayPal/mixin/swag-paypal-merchant-information.mixin';

import 'SwagPayPal/app/store/swag-paypal-settings.store';
import 'SwagPayPal/app/store/swag-paypal-merchant-information.store';

afterEach(() => {
    Shopware.Store.get('swagPayPalSettings').$reset();
    Shopware.Store.get('swagPayPalMerchantInformation').$reset();
});

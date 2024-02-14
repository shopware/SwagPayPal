// Import all necessary Storefront plugins
import SwagPayPalExpressCheckoutButton from './page/swag-paypal.express-checkout';
import SwagPayPalSmartPaymentButtons from './checkout/swag-paypal.smart-payment-buttons';
import SwagPayPalPlusPaymentWall from './checkout/swag-paypal.plus-payment-wall';
import SwagPayPalInstallmentBanner from './page/swag-paypal.installment-banner';
import SwagPaypalAcdcFields from './checkout/swag-paypal.acdc-fields';
import SwagPaypalPuiPolling from './swag-paypal.pui-polling';
import SwagPaypalSepa from './checkout/swag-paypal.sepa';
import SwagPaypalVenmo from './checkout/swag-paypal.venmo';
import SwagPaypalApplePay from './checkout/swag-paypal.apple-pay';
import SwagPaypalGooglePay from './checkout/swag-paypal.google-pay';
import SwagPaypalPayLater from './checkout/swag-paypal.pay-later';
import SwagPaypalFundingEligibility from './page/swag-paypal.funding-eligibility';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register(
    'SwagPayPalExpressButton',
    SwagPayPalExpressCheckoutButton,
    '[data-swag-paypal-express-button]',
);
PluginManager.register(
    'SwagPayPalSmartPaymentButtons',
    SwagPayPalSmartPaymentButtons,
    '[data-swag-paypal-smart-payment-buttons]',
);
PluginManager.register(
    'SwagPaypalAcdcFields',
    SwagPaypalAcdcFields,
    '[data-swag-paypal-acdc-fields]',
);
PluginManager.register(
    'SwagPayPalPlusPaymentWall',
    SwagPayPalPlusPaymentWall,
    '[data-swag-paypal-payment-wall]',
);
PluginManager.register(
    'SwagPayPalInstallmentBanner',
    SwagPayPalInstallmentBanner,
    '[data-swag-paypal-installment-banner]',
);
PluginManager.register(
    'SwagPaypalPuiPolling',
    SwagPaypalPuiPolling,
    '[data-swag-paypal-pui-polling]',
);
PluginManager.register(
    'SwagPaypalSepa',
    SwagPaypalSepa,
    '[data-swag-paypal-sepa]',
);
PluginManager.register(
    'SwagPaypalVenmo',
    SwagPaypalVenmo,
    '[data-swag-paypal-venmo]',
);
PluginManager.register(
    'SwagPaypalApplePay',
    SwagPaypalApplePay,
    '[data-swag-paypal-apple-pay]',
);
PluginManager.register(
    'SwagPaypalGooglePay',
    SwagPaypalGooglePay,
    '[data-swag-paypal-google-pay]',
);
PluginManager.register(
    'SwagPaypalPayLater',
    SwagPaypalPayLater,
    '[data-swag-paypal-pay-later]',
);
PluginManager.register(
    'SwagPaypalFundingEligibility',
    SwagPaypalFundingEligibility,
    '[data-swag-paypal-funding-eligibility]',
);

// Import all necessary Storefront plugins and scss files
import SwagPayPalExpressCheckoutButton from './express-checkout-button/swag-paypal.express-checkout';
import SwagPayPalSmartPaymentButtons from './smart-payment-buttons/swag-paypal.smart-payment-buttons';
import PayPalSelector from './paypal-selector/paypal-selector';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('SwagPayPalExpressButton', SwagPayPalExpressCheckoutButton, '[data-swag-paypal-express-button]');
PluginManager.register('SwagPayPalSmartPaymentButtons', SwagPayPalSmartPaymentButtons, '[data-swag-paypal-smart-payment-buttons]');
PluginManager.register('PayPalSelector', PayPalSelector, 'input[name="isPayPalExpressCheckout"]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}

// Import all necessary Storefront plugins and scss files
import SwagPayPalExpressCheckoutButton from './express-checkout-button/swag-paypal.express-checkout';
import SwagPayPalSmartPaymentButtons from './smart-payment-buttons/swag-paypal.smart-payment-buttons';
import SwagPayPalPlusPaymentWall from './plus/payment-wall';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('SwagPayPalExpressButton', SwagPayPalExpressCheckoutButton, '[data-swag-paypal-express-button]');
PluginManager.register('SwagPayPalSmartPaymentButtons', SwagPayPalSmartPaymentButtons, '[data-swag-paypal-smart-payment-buttons]');
PluginManager.register('SwagPayPalPlusPaymentWall', SwagPayPalPlusPaymentWall, '[data-payPalPaymentWall="true"]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}

// Import all necessary Storefront plugins and scss files
import SwagPayPalExpressCheckoutButton from './express-checkout-button/swag-paypal.express-checkout';
import PayPalSelector from './paypal-selector/paypal-selector';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('SwagPayPalExpressButton', SwagPayPalExpressCheckoutButton, '[data-swag-paypal-express-button]');
PluginManager.register('PayPalSelector', PayPalSelector, 'input[name="isPayPalExpressCheckout"]');

// Import all necessary Storefront plugins and scss files
import SwagPayPalExpressCheckoutButton from './express-checkout-button/swag-paypal.express-checkout';
import SwagPayPalSmartPaymentButtons from './smart-payment-buttons/swag-paypal.smart-payment-buttons';
import SwagPayPalMarks from './smart-payment-buttons/swag-paypal.marks';
import SwagPayPalPlusPaymentWall from './plus/payment-wall';
import SwagPayPalInstallmentBanner from './installment/swag-paypal.installment-banner';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('SwagPayPalExpressButton', SwagPayPalExpressCheckoutButton, '[data-swag-paypal-express-button]');
PluginManager.register('SwagPayPalSmartPaymentButtons', SwagPayPalSmartPaymentButtons, '[data-swag-paypal-smart-payment-buttons]');
PluginManager.register('SwagPayPalMarks', SwagPayPalMarks, '[data-swag-paypal-marks]');
PluginManager.register('SwagPayPalPlusPaymentWall', SwagPayPalPlusPaymentWall, '[data-swag-paypal-payment-wall]');
PluginManager.register('SwagPayPalInstallmentBanner', SwagPayPalInstallmentBanner, '[data-swag-paypal-installment-banner]');

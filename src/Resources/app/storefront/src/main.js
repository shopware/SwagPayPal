// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register(
    'SwagPayPalExpressButton',
    () => import('./page/swag-paypal.express-checkout'),
    '[data-swag-paypal-express-button]',
);
PluginManager.register(
    'SwagPayPalSmartPaymentButtons',
    () => import('./checkout/swag-paypal.smart-payment-buttons'),
    '[data-swag-paypal-smart-payment-buttons]',
);
PluginManager.register(
    'SwagPaypalAcdcFields',
    () => import('./checkout/swag-paypal.acdc-fields'),
    '[data-swag-paypal-acdc-fields]',
);
PluginManager.register(
    'SwagPayPalInstallmentBanner',
    () => import('./page/swag-paypal.installment-banner'),
    '[data-swag-paypal-installment-banner]',
);
PluginManager.register(
    'SwagPaypalPuiPolling',
    () => import('./swag-paypal.pui-polling'),
    '[data-swag-paypal-pui-polling]',
);
PluginManager.register(
    'SwagPaypalSepa',
    () => import('./checkout/swag-paypal.sepa'),
    '[data-swag-paypal-sepa]',
);
PluginManager.register(
    'SwagPaypalVenmo',
    () => import('./checkout/swag-paypal.venmo'),
    '[data-swag-paypal-venmo]',
);
PluginManager.register(
    'SwagPaypalApplePay',
    () => import('./checkout/swag-paypal.apple-pay'),
    '[data-swag-paypal-apple-pay]',
);
PluginManager.register(
    'SwagPaypalGooglePay',
    () => import('./checkout/swag-paypal.google-pay'),
    '[data-swag-paypal-google-pay]',
);
PluginManager.register(
    'SwagPaypalPayLater',
    () => import('./checkout/swag-paypal.pay-later'),
    '[data-swag-paypal-pay-later]',
);
PluginManager.register(
    'SwagPaypalFundingEligibility',
    () => import('./page/swag-paypal.funding-eligibility'),
    '[data-swag-paypal-funding-eligibility]',
);


PluginManager.register(
    'SwagPaypalCheckoutPaypal',
    () => import('./payment/swag-paypal.checkout.paypal'),
    '[data-swag-paypal-checkout-paypal]',
);
PluginManager.register(
    'SwagPaypalCheckoutPayLater',
    () => import('./payment/swag-paypal.checkout.pay-later'),
    '[data-swag-paypal-checkout-pay-later]',
);
PluginManager.register(
    'SwagPaypalCheckoutVenmo',
    () => import('./payment/swag-paypal.checkout.venmo'),
    '[data-swag-paypal-checkout-venmo]',
);
PluginManager.register(
    'SwagPaypalCheckoutGooglePay',
    () => import('./payment/swag-paypal.checkout.google-pay'),
    '[data-swag-paypal-checkout-google-pay]',
);
PluginManager.register(
    'SwagPaypalCheckoutApplePay',
    () => import('./payment/swag-paypal.checkout.apple-pay'),
    '[data-swag-paypal-checkout-apple-pay]',
);

PluginManager.register(
    'SwagPaypalCheckoutCardFields',
    () => import('./payment/swag-paypal.checkout.card-fields'),
    '[data-swag-paypal-checkout-card-fields]',
);


PluginManager.register(
    'SwagPaypalExpressPaypal',
    () => import('./payment/swag-paypal.express.paypal'),
    '[data-swag-paypal-express-paypal]',
);
PluginManager.register(
    'SwagPaypalExpressPayLater',
    () => import('./payment/swag-paypal.express.pay-later'),
    '[data-swag-paypal-express-pay-later]',
);
PluginManager.register(
    'SwagPaypalExpressVenmo',
    () => import('./payment/swag-paypal.express.venmo'),
    '[data-swag-paypal-express-venmo]',
);

PluginManager.register(
    'SwagPaypalEligibility',
    () => import('./page/swag-paypal.eligibility'),
    '[data-swag-paypal-eligibility]',
);
PluginManager.register(
    'SwagPaypalMessages',
    () => import('./page/swag-paypal.messages'),
    '[data-swag-paypal-messages]',
);

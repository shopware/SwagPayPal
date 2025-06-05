import { ui } from '@shopware-ag/meteor-admin-sdk';

Shopware.Component.register('swag-paypal-method-domain-association', () => import('./component/swag-paypal-method-domain-association'));
Shopware.Component.register('swag-paypal-method-merchant-information', () => import('./component/swag-paypal-method-merchant-information'));
Shopware.Component.register('swag-paypal-payment-method', () => import('./component/swag-paypal-payment-method'));

Shopware.Component.register('swag-paypal-method-card', () => import('./view/swag-paypal-method-card'));

Shopware.Component.override('sw-plugin-box', () => import('./extension/sw-plugin-box'));
Shopware.Component.override('sw-settings-payment-detail', () => import('./extension/sw-settings-payment/sw-settings-payment-detail'));

ui.module.payment.overviewCard.add({
    positionId: 'swag-paypal-method-card-before',
    component: 'swag-paypal-method-card',
    paymentMethodHandlers: [
        'handler_swag_trustlyapmhandler',
        'handler_swag_sofortapmhandler',
        'handler_swag_p24apmhandler',
        'handler_swag_oxxoapmhandler',
        'handler_swag_mybankapmhandler',
        'handler_swag_multibancoapmhandler',
        'handler_swag_idealapmhandler',
        'handler_swag_giropayapmhandler',
        'handler_swag_epsapmhandler',
        'handler_swag_blikapmhandler',
        'handler_swag_bancontactapmhandler',
        'handler_swag_sepahandler',
        'handler_swag_acdchandler',
        'handler_swag_puihandler',
        'handler_swag_paypalpaymenthandler',
        'handler_swag_pospayment',
        'handler_swag_venmohandler',
        'handler_swag_paylaterhandler',
        'handler_swag_applepayhandler',
        'handler_swag_googlepayhandler',
    ],
});

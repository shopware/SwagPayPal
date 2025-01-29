import { ui } from '@shopware-ag/meteor-admin-sdk';

import './mixin/swag-paypal-credentials-loader.mixin';
import './mixin/swag-paypal-notification.mixin';
import './mixin/swag-paypal-pos-catch-error.mixin';
import './mixin/swag-paypal-pos-log-label.mixin';
import './mixin/swag-paypal-settings.mixin';
import './mixin/swag-paypal-merchant-information.mixin';

import './module/extension';
import './module/swag-paypal-disputes';
import './module/swag-paypal-payment';
import './module/swag-paypal-pos';

import './init/api-service.init';
import './init/translation.init';
import './init/svg-icons.init';

const bootPromise = window.Shopware ? Shopware.Plugin.addBootPromise() : () => {};

(async () => {
    if (Shopware.Feature.isActive('PAYPAL_SETTINGS_TWEAKS')) {
        await import('./app');
        // @ts-expect-error - yes it's not a module
        await import('./module/swag-paypal-settings');
        // @ts-expect-error - yes it's not a module
        await import('./module/swag-paypal-method');
    } else {
        await import('./module/swag-paypal');
    }

    // @ts-expect-error - bootPromise has a wrong doc type
    bootPromise();
})();

if (!Shopware.Feature.isActive('PAYPAL_SETTINGS_TWEAKS')) {
    /**
     * @deprecated tag:v10.0.0 - Will be replaced by `swag-paypal-method`
     */
    ui.module.payment.overviewCard.add({
        positionId: 'swag-paypal-overview-card-before',
        component: 'swag-paypal-overview-card',
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
}

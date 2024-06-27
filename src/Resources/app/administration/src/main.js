import { location } from '@shopware-ag/meteor-admin-sdk';

const bootPromise = window.Shopware ? Shopware.Plugin.addBootPromise() : undefined;

(async () => {
    if (!location.isIframe()) {
        await import('./mixin/swag-paypal-credentials-loader.mixin');
        await import('./mixin/swag-paypal-pos-catch-error.mixin');
        await import('./mixin/swag-paypal-pos-log-label.mixin');

        await import('./module/extension/sw-first-run-wizard/sw-first-run-wizard-paypal-credentials');
        await import('./module/extension/sw-sales-channel-modal-detail');
        await import('./module/extension/sw-sales-channel-modal-grid');
        await import('./module/extension/sw-settings-payment/sw-settings-payment-list');
        await import('./module/extension/sw-settings-payment/sw-settings-payment-detail');
        await import('./module/extension/sw-settings-shipping/sw-settings-shipping-detail');
        await import('./module/extension/sw-settings-payment/components/swag-paypal-overview-card');

        await import('./module/swag-paypal');
        await import('./module/swag-paypal-payment');

        await import('./init/api-service.init');
        await import('./init/translation.init');
        await import('./init/svg-icons.init');

        await import('./module/swag-paypal-pos');

        await import('./module/swag-paypal-disputes');
    }

    if (bootPromise) {
        bootPromise();
    }
})();

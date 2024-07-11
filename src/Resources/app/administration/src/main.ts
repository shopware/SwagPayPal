import { location } from '@shopware-ag/meteor-admin-sdk';

const bootPromise = window.Shopware
    ? (Shopware.Plugin.addBootPromise() as () => never) // it's wrongly typed as object in shopware
    : undefined;

(async () => {
    if (!location.isIframe()) {
        await import('./mixin/swag-paypal-credentials-loader.mixin');
        await import('./mixin/swag-paypal-pos-catch-error.mixin');
        await import('./mixin/swag-paypal-pos-log-label.mixin');

        await import('./module/extension');
        await import('./module/swag-paypal');
        await import('./module/swag-paypal-disputes');
        await import('./module/swag-paypal-payment');
        await import('./module/swag-paypal-pos');

        await import('./init/api-service.init');
        await import('./init/translation.init');
        await import('./init/svg-icons.init');
    }

    if (bootPromise) {
        bootPromise();
    }
})();

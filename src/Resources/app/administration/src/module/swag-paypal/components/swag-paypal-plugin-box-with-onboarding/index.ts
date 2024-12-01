import template from './sw-plugin-box-with-onboarding.html.twig';

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement
 */
Shopware.Component.wrapComponentConfig({
    template,

    props: {
        paymentMethod: {
            type: Object,
            required: true,
        },
    },
});

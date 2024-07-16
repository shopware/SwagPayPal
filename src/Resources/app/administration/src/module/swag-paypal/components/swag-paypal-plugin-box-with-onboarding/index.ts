import template from './sw-plugin-box-with-onboarding.html.twig';

Shopware.Component.wrapComponentConfig({
    template,

    props: {
        paymentMethod: {
            type: Object,
            required: true,
        },
    },
});

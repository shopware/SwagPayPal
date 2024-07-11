import template from './sw-plugin-box-with-onboarding.html.twig';

const { Component } = Shopware;

Component.extend('swag-paypal-plugin-box-with-onboarding', 'sw-plugin-box', {
    template,

    props: {
        paymentMethod: {
            type: Object,
            required: true,
        },
    },
});

import './swag-paypal-settings-hint.scss';
import template from './swag-paypal-settings-hint.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-settings-hint', {
    template,

    props: {
        hintText: {
            type: String,
            required: true,
        },
    },
});

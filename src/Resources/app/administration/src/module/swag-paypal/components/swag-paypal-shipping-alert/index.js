import template from './swag-paypal-shipping-alert.html.twig';
import './swag-paypal-shipping-alert.scss';

const { Component } = Shopware;

/**
 * @private may be removed oder changed at any time
 */
Component.register('swag-paypal-shipping-alert', {
    template,

    data() {
        return {
            closed: true,
            localStorageKey: 'swag-paypal.shipping-alert.closed',
        };
    },

    computed: {
        alertClasses() {
            return {
                'swag-paypal-shipping-alert': true,
                'swag-paypal-shipping-alert__closed': this.closed,
            };
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.closed = window.localStorage.getItem(this.localStorageKey) === 'true';
        },

        close() {
            this.closed = true;

            window.localStorage.setItem(this.localStorageKey, 'true');
        },
    },
});

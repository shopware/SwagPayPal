import template from './swag-paypal-izettle-status.html.twig';
import './swag-paypal-izettle-status.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-status', {
    template,

    props: {
        title: {
            type: String,
            required: false
        },
        status: {
            type: String,
            required: true
        },
        detail: {
            type: String,
            required: false
        },
        icon: {
            type: String,
            required: true
        },
        iconAnimated: {
            type: Boolean,
            required: false,
            default: false
        },
        variant: {
            type: String,
            default: 'info',
            validValues: ['info', 'warning', 'error', 'success'],
            validator(value) {
                return ['info', 'warning', 'error', 'success'].includes(value);
            }
        }
    },

    computed: {
        statusClasses() {
            return [
                'swag-paypal-izettle-status',
                `swag-paypal-izettle-status--${this.variant}`
            ];
        }
    }
});

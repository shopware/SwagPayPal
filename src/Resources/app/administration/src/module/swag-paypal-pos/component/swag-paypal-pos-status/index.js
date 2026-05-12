import template from './swag-paypal-pos-status.html.twig';
import './swag-paypal-pos-status.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-status', {
    template,

    props: {
        title: {
            type: String,
            required: false,
            default: '',
        },
        status: {
            type: String,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        disabledText: {
            type: String,
            required: false,
            default: '',
        },
        icon: {
            type: String,
            required: true,
        },
        iconAnimated: {
            type: Boolean,
            required: false,
            default: false,
        },
        subIcon: {
            type: String,
            required: false,
            default: '',
        },
        showSubStatus: {
            type: Boolean,
            required: false,
            default: false,
        },
        isLoading: {
            type: Boolean,
            required: true,
        },
        variant: {
            type: String,
            default: 'info',
            validValues: ['info', 'warning', 'error', 'success'],
            validator(value) {
                return ['info', 'warning', 'error', 'success'].includes(value);
            },
        },
        subVariant: {
            type: String,
            default: 'info',
            validValues: ['info', 'warning', 'error', 'success'],
            validator(value) {
                return ['info', 'warning', 'error', 'success'].includes(value);
            },
        },
    },

    computed: {
        statusClasses() {
            return {
                'swag-paypal-pos-status': true,
                [`swag-paypal-pos-status--${this.variant}`]: true,
                'swag-paypal-pos-status--disabled': this.disabled,
            };
        },

        iconClasses() {
            return {
                'swag-paypal-pos-status__icon': true,
                'swag-paypal-pos-status__icon-animated': this.iconAnimated,
            };
        },

        subIconClasses() {
            return [
                'swag-paypal-pos-status__subicon',
                `swag-paypal-pos-status--${this.subVariant}`,
            ];
        },

        showSubIcon() {
            return this.subIcon !== null && this.subIcon !== undefined && this.subIcon !== this.icon;
        },
    },
});

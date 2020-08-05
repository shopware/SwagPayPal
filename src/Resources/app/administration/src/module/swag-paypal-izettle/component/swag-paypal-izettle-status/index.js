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
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        disabledText: {
            type: String,
            required: false,
            default: ''
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
        subIcon: {
            type: String,
            required: false
        },
        showSubStatus: {
            type: Boolean,
            required: false,
            default: false
        },
        isLoading: {
            type: Boolean,
            required: true
        },
        variant: {
            type: String,
            default: 'info',
            validValues: ['info', 'warning', 'error', 'success'],
            validator(value) {
                return ['info', 'warning', 'error', 'success'].includes(value);
            }
        },
        subVariant: {
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
            return {
                'swag-paypal-izettle-status': true,
                [`swag-paypal-izettle-status--${this.variant}`]: true,
                'swag-paypal-izettle-status--disabled': this.disabled
            };
        },

        iconClasses() {
            return {
                'swag-paypal-izettle-status__icon': true,
                'swag-paypal-izettle-status__icon-animated': this.iconAnimated
            };
        },

        subIconClasses() {
            return [
                'swag-paypal-izettle-status__subicon',
                `swag-paypal-izettle-status--${this.subVariant}`
            ];
        },

        showSubIcon() {
            return this.subIcon !== null && this.subIcon !== undefined && this.subIcon !== this.icon;
        }
    }
});

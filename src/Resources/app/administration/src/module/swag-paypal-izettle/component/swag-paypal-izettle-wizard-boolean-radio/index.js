import template from './swag-paypal-izettle-wizard-boolean-radio.html.twig';
import './swag-paypal-izettle-wizard-boolean-radio.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-boolean-radio', {
    template,

    model: {
        prop: 'value',
        event: 'change'
    },

    props: {
        value: {
            type: Boolean,
            required: false,
            default: true
        },

        optionTrue: {
            type: Object,
            required: true,
            validator(value) {
                return value.hasOwnProperty('name');
            }
        },

        optionFalse: {
            type: Object,
            required: true,
            validator(value) {
                return value.hasOwnProperty('name');
            }
        }
    },

    computed: {
        options() {
            return [
                {
                    value: true,
                    ...this.optionTrue
                },
                {
                    value: false,
                    ...this.optionFalse
                }
            ];
        },

        castedValue: {
            get() {
                return this.value;
            },

            set(val) {
                this.$emit('change', val);
            }
        }
    }
});

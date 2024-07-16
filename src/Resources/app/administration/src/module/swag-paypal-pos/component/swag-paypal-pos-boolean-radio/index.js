import template from './swag-paypal-pos-boolean-radio.html.twig';
import './swag-paypal-pos-boolean-radio.scss';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-pos-boolean-radio', {
    template,

    inject: ['feature'],

    props: {
        value: {
            type: Boolean,
            required: false,
            default: false,
        },

        optionTrue: {
            type: Object,
            required: true,
            validator(value) {
                return Object.hasOwn(value, 'name');
            },
        },

        optionFalse: {
            type: Object,
            required: true,
            validator(value) {
                return Object.hasOwn(value, 'name');
            },
        },
    },

    data() {
        return {
            inputId: utils.createId(),
        };
    },

    computed: {
        options() {
            return [
                {
                    value: true,
                    ...this.optionTrue,
                },
                {
                    value: false,
                    ...this.optionFalse,
                },
            ];
        },

        castedValue: {
            get() {
                return this.value;
            },

            set(val) {
                this.$emit('update:value', val);
            },
        },

        name() {
            return `swag-paypal-pos-boolean-radio-${this.inputId}`;
        },
    },
});

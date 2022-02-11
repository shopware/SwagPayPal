import template from './swag-paypal-pos-boolean-radio.html.twig';
import './swag-paypal-pos-boolean-radio.scss';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.register('swag-paypal-pos-boolean-radio', {
    template,

    model: {
        prop: 'value',
        event: 'change',
    },

    props: {
        value: {
            type: Boolean,
            required: false,
            // eslint-disable-next-line vue/no-boolean-default
            default: true,
        },

        optionTrue: {
            type: Object,
            required: true,
            validator(value) {
                return value.hasOwnProperty('name');
            },
        },

        optionFalse: {
            type: Object,
            required: true,
            validator(value) {
                return value.hasOwnProperty('name');
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
                this.$emit('change', val);
            },
        },

        name() {
            return `swag-paypal-pos-boolean-radio-${this.inputId}`;
        },
    },
});

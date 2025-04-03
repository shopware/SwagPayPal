import template from './swag-paypal-textarea-field.html.twig';

const { Component } = Shopware;

/**
 * @deprecated tag:v10.0.0 - Will be removed, use `max-length` of mt-textarea
 *
 * @description textarea input field. But this one allows attribute downpassing to the input field instead of the block.
 */
Component.register('swag-paypal-textarea-field', {
    template,

    emits: ['update:value'],

    props: {
        value: {
            type: String,
            required: false,
            default: null,
        },
        maxLength: {
            type: [String, Number],
            required: false,
            default: null,
        },
    },

    computed: {
        allowedLength() {
            return this.maxLength ? Number(this.maxLength) : null;
        },

        currentLength() {
            return this.maxLength ? (this.value ?? '').length : null;
        },

        hintText() {
            return this.allowedLength
                ? `${this.currentLength}/${this.allowedLength}`
                : null;
        },
    },

    methods: {
        updateValue(value) {
            if (this.allowedLength && (value ?? '').length > this.allowedLength) {
                value = (value ?? '').substring(0, this.allowedLength);
            }

            this.$emit('update:value', value);
        },
    },
});

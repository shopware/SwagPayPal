import template from './sw-paypal-locale-field.html.twig';
import './sw-paypal-locale-field.scss';

const { Component } = Shopware;
const { debounce } = Shopware.Utils;

Component.extend('sw-paypal-locale-field', 'sw-text-field', {
    template,

    data() {
        return {
            error: null
        };
    },

    methods: {
        onInput: debounce(function onInput(event) {
            const value = event.target.value;
            const localeCodeRegex = /^[a-z]{2}_[A-Z]{2}$/;

            this.$emit('change', event.target.value || '');

            if (!value || localeCodeRegex.exec(value)) {
                this.preventSave(false);
                this.error = null;
                return;
            }

            this.preventSave(true);
            this.error = {
                code: 1,
                detail: this.$tc('swag-paypal.settingForm.locale-field.error.detail')
            };
        }, 350),

        preventSave(mode) {
            this.$emit('preventSave', mode);
        }
    }
});

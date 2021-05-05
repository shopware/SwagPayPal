const { Component } = Shopware;

Component.extend('swag-paypal-pos-url-field', 'sw-url-field', {
    props: {
        error: {
            type: Object,
            required: false,
            default: null
        }
    },

    watch: {
        error: {
            handler() {
                this.errorUrl = this.error;

                if (this.error === null || this.error === 'undefined') {
                    this.checkInput(this.currentValue);
                }
            },
            immediate: true
        },

        errorUrl() {
            if (this.errorUrl === null || this.errorUrl === 'undefined') {
                this.errorUrl = this.error;
            }
        }
    },

    methods: {
        checkInput(inputValue) {
            this.currentValue = inputValue;
            const urlPattern = /^\s*https?:\/\//;
            if (urlPattern.test(inputValue)) {
                this.currentValue = this.currentValue.replace(urlPattern, '');
            }

            this.validateCurrentValue();
        },

        changeMode() {
            // override, so no disabling of SSL is possible
        }
    }
});

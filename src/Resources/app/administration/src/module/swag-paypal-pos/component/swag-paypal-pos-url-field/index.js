const { Component } = Shopware;

Component.extend('swag-paypal-pos-url-field', 'sw-url-field', {
    props: {
        error: {
            type: Object,
            required: false
        }
    },

    watch: {
        error: {
            handler() {
                this.errorUrl = this.error;

                if (this.error === null || this.error === 'undefined') {
                    this.validateCurrentValue();
                }
            },
            immediate: true
        },

        errorUrl() {
            if (this.errorUrl === null || this.errorUrl === 'undefined') {
                this.errorUrl = this.error;
            }
        }
    }
});

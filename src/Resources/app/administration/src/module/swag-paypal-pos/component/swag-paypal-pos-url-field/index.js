const { Component } = Shopware;

Component.extend('swag-paypal-pos-url-field', 'sw-url-field', {
    methods: {
        changeMode() {
            // override, so no disabling of SSL is possible
        },

        getSSLMode() {
            // override, so no disabling of SSL is possible
            return true;
        },
    },
});

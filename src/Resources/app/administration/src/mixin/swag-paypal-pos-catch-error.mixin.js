const { Mixin } = Shopware;

Mixin.register('swag-paypal-pos-catch-error', {
    methods: {
        /**
         * Creates a notification, if an error has been returned
         *
         * @param {string} snippet of the notification
         * @param {Object} errorResponse
         */
        catchError(snippet, errorResponse) {
            let message = snippet ? this.$root.$tc(snippet) : '';

            try {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    const errorText = errorResponse.response.data.errors.map((error) => {
                        return error.detail;
                    }).join(' / ');

                    if (errorText) {
                        message = message ? `${message}: ${errorText}` : errorText;
                    }
                }
            } finally {
                this.createNotification({
                    variant: 'error',
                    title: this.$root.$tc('global.default.error'),
                    message
                });
            }
        }
    }
});

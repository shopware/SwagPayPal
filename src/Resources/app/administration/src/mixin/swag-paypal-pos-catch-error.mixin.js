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
            // mixins otherwise don't get i18n
            this._i18n = this.$root._i18n;

            let message = snippet ? this.$tc(snippet) : '';

            try {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    const errorText = errorResponse.response.data.errors.map((error) => {
                        if (error.code === 'SWAG_PAYPAL_POS__EXISTING_POS_ACCOUNT') {
                            message = this.$tc('swag-paypal-pos.authentication.messageDuplicateError');
                        }

                        if (error.hasOwnProperty('meta') && error.meta.hasOwnProperty('parameters')) {
                            if (error.meta.parameters.salesChannelIds) {
                                return `<br>${error.meta.parameters.salesChannelIds}`;
                            }

                            if (error.meta.parameters.message) {
                                return `${error.meta.parameters.message} (${error.meta.parameters.name})`;
                            }

                            if (error.meta.parameters.name) {
                                return error.meta.parameters.name;
                            }
                        }

                        return error.detail;
                    }, this).join(' / ');

                    if (errorText) {
                        message = message ? `${message}: ${errorText}` : errorText;
                    }
                }
            } finally {
                this.createNotificationError({ message });
            }
        },
    },
});

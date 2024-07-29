import type * as PayPal from 'src/types';

export default Shopware.Mixin.register('swag-paypal-pos-catch-error', Shopware.Component.wrapComponentConfig({
    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    methods: {
        /**
         * Creates a notification, if an error has been returned
         */
        catchError(snippet: string, errorResponse: PayPal.ServiceError) {
            const formatMessage = (message: string, error: PayPal.HttpError) => {
                message = snippet ? this.$tc(snippet) : message;

                const params = error.meta?.parameters;
                if (params) {
                    if (params.salesChannelIds) {
                        message += `: <br>${params.salesChannelIds}`;
                    } else if (params.message) {
                        message += `: ${params.message} (${params.name ?? ''})`;
                    }

                    if (params.name) {
                        message += `: ${params.name}`;
                    }
                }

                return message;
            };

            this.createNotificationFromError({ errorResponse, formatMessage });
        },
    },
}));

import type * as PayPal from 'SwagPayPal/types';

export default Shopware.Mixin.register('swag-paypal-pos-catch-error', Shopware.Component.wrapComponentConfig({
    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    methods: {
        /**
         * Creates a notification, if an error has been returned
         */
        catchError(snippet: null | string, errorResponse: PayPal.ServiceError) {
            const errors = errorResponse?.response?.data?.errors ?? [];
            const errorMessage = errors.map((error) => {
                let message = '';

                const params = error.meta?.parameters;
                if (params) {
                    if (params.salesChannelIds) {
                        message += `<br>${params.salesChannelIds}`;
                    } else if (params.message) {
                        message += `${params.message} (${params.name ?? ''})`;
                    }

                    if (params.name) {
                        if (message) {
                            message += ': ';
                        }

                        message += `${params.name}`;
                    }
                }

                message ||= this.createMessageFromError({ errors: [error] });

                return message;
            }).join('<br>');


            this.createNotificationError({
                title: this.$t('swag-paypal.notifications.posError.title'),
                message: (snippet ? this.$t(snippet) + ': ' : '') + errorMessage,
            });
        },
    },
}));

import type * as PayPal from 'src/types';

type ShopwareErrorMetaExt = {
    meta?: {
        parameters?: {
            salesChannelIds?: string[];
            message?: string;
            name?: string;
        };
    };
}

export default Shopware.Mixin.register('swag-paypal-pos-catch-error', Shopware.Component.wrapComponentConfig({
    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    methods: {
        /**
         * Creates a notification, if an error has been returned
         */
        catchError(snippet: string, errorResponse: PayPal.ServiceError) {
            // mixins otherwise don't get i18n
            // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment, @typescript-eslint/no-unsafe-member-access
            this._i18n = this.$root._i18n;

            let message = snippet ? this.$tc(snippet) : '';

            try {
                if (errorResponse.response?.data?.errors) {
                    const errorText = errorResponse.response.data.errors.map((error: ShopwareHttpError&ShopwareErrorMetaExt) => {
                        if (error.code === 'SWAG_PAYPAL_POS__EXISTING_POS_ACCOUNT') {
                            message = this.$tc('swag-paypal-pos.authentication.messageDuplicateError');
                        }

                        const params = error.meta?.parameters;
                        if (params) {
                            if (params.salesChannelIds) {
                                return `<br>${params.salesChannelIds}`;
                            }

                            if (params.message) {
                                return `${params.message} (${params.name ?? ''})`;
                            }

                            if (params.name) {
                                return params.name;
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
}));
import type * as PayPal from 'SwagPayPal/types';

export default Shopware.Mixin.register('swag-paypal-notification', Shopware.Component.wrapComponentConfig({
    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    methods: {
        /**
         * Creates a message from a http error.
         * Can handle axios responses, plain object containing errors or an array of errors.
         */
        createMessageFromError(httpError: PayPal.ServiceError | { errors?: PayPal.HttpError[] }): string {
            const errors = (httpError as { errors?: PayPal.HttpError[] }).errors
                ?? (httpError as PayPal.ServiceError)?.response?.data?.errors
                ?? [];

            const messages = errors.map((error) => {
                const message = typeof error.meta?.parameters?.message === 'string'
                    ? error.meta.parameters.message || error.detail
                    : error.detail;

                const snippet = `swag-paypal.errors.${error.code}`;
                const translation = this.$t(snippet, { message });

                return snippet !== translation ? translation : message;
            });

            return messages.join('<br>');
        },
    },
}));

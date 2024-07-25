import type * as PayPal from 'src/types';

/**
 * Options to handle a service error.
 * @property errorResponse - The error response from the service.
 * @property formatMessage - A function to format the error message.
 * @property title - The title of the error notification. Can be string or snippet. Will be prepended to the error message.
 */
export type HandleOptions = {
    errorResponse: PayPal.ServiceError;
    formatMessage?: (translatedMessage: string, error: PayPal.HttpError) => string;
    title?: string;
}

const UnknownError: PayPal.HttpError = {
    code: 'UNKNOWN',
    status: '500',
    title: 'Unknown error',
    detail: 'Unknown error',
};

export default Shopware.Mixin.register('swag-paypal-notification', Shopware.Component.wrapComponentConfig({
    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    methods: {
        /**
         * Handles a service error and creates a notification for each error.
         * If the errorResponse is undefined, a generic error notification will be created.
         * If the errorResponse is not a ShopwareHttpError, the errorResponse will be used as message.
         */
        createNotificationFromError(options: HandleOptions): void {
            let { title } = options;
            const { errorResponse, formatMessage = ((message: string) => message) } = options;

            let errors = errorResponse?.response?.data?.errors;

            if (!errorResponse) { // no error given -> show generic error
                errors = [UnknownError];
            } else if (!errors) { // not a ShopwareHttpError -> show errorResponse as message
                const formattedMessage = formatMessage(String(errorResponse), UnknownError);
                this.createNotificationError({ message: formattedMessage });

                return;
            }

            const messages: string[] = errors.map((error) => {
                const message = typeof error.meta?.parameters?.message === 'string'
                    ? error.meta.parameters.message
                    : error.detail;
                const snippet = `swag-paypal.errors.${error.code}`;
                const translation = this.$tc(snippet, 0, { message });

                if (snippet !== translation) {
                    return formatMessage(translation, error);
                }

                return formatMessage(message, error);
            });

            if (title) {
                const translation = this.$tc(title);
                title = title !== translation ? translation : title;
            }

            for (let i = 0; i < messages.length; i += 1) {
                this.createNotificationError({ message: messages[i], title });
            }
        },
    },
}));

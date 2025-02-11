import type * as PayPal from 'SwagPayPal/types';

export default Shopware.Mixin.register('swag-paypal-merchant-information', Shopware.Component.wrapComponentConfig({
    inject: [
        'SwagPayPalSettingsService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    computed: {
        merchantInformationStore() {
            return Shopware.Store.get('swagPayPalMerchantInformation');
        },
    },

    watch: {
        'merchantInformationStore.salesChannel': {
            immediate: true,
            handler(salesChannel: string | null) {
                this.fetchMerchantInformation(salesChannel);
            },
        },
        'savingSettings'(savingSettings: string) {
            if (savingSettings === 'success') {
                this.fetchMerchantInformation(this.merchantInformationStore.salesChannel);
            }
        },
    },

    beforeCreate() {
        // computed properties aren't ready yet
        Shopware.Store.get('swagPayPalMerchantInformation').$reset();
    },

    methods: {
        async fetchMerchantInformation(salesChannel: string | null) {
            if (this.merchantInformationStore.has(salesChannel)) {
                return;
            }

            const merchantInformation = await this.SwagPayPalSettingsService
                .getMerchantInformation(salesChannel)
                .catch((error: PayPal.ServiceError) => {
                    this.createNotificationError({
                        title: this.$t('swag-paypal.notifications.merchantInformation.title'),
                        message: this.$t('swag-paypal.notifications.merchantInformation.errorMessage', {
                            message: this.createMessageFromError(error),
                        }),
                    });

                    return null;
                });

            if (merchantInformation) {
                this.merchantInformationStore.set(salesChannel, merchantInformation);
            }
        },
    },
}));

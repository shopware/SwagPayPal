import type * as PayPal from 'SwagPayPal/types';
import template from './sw-settings-payment-detail.html.twig';
import './sw-settings-payment-detail.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPayPalSettingsService',
    ],

    data(): {
        capabilities: PayPal.Setting<'merchant_information'>['capabilities'];
    } {
        return {
            capabilities: {},
        };
    },

    computed: {
        needsOnboarding(): boolean {
            // no capabilities, no paypal payment method
            if (!this.capabilities) {
                return false;
            }

            // @ts-expect-error - paymentMethod is from extended component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.capabilities[this.paymentMethod.id] === 'inactive';
        },
    },

    watch: {
        paymentMethod(paymentMethod) {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access, @typescript-eslint/no-unsafe-call
            if (paymentMethod?.technicalName && paymentMethod.technicalName.startsWith('swag_paypal_')) {
                this.fetchMerchantCapabilities();
            }
        },
    },

    methods: {
        async fetchMerchantCapabilities() {
            const merchantInformation = await this.SwagPayPalSettingsService.getMerchantInformation();
            this.capabilities = merchantInformation.capabilities ?? {};
        },
    },
});


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
            // @ts-expect-error - paymentMethod is from extended component
            if (!this.paymentMethod || !this.capabilities) {
                return true;
            }

            // @ts-expect-error - paymentMethod is from extended component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.capabilities[this.paymentMethod.id] === 'inactive';
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchMerchantCapabilities();
        },

        async fetchMerchantCapabilities() {
            const merchantInformation = await this.SwagPayPalSettingsService.getMerchantInformation();
            this.capabilities = merchantInformation.capabilities ?? {};
        },
    },
});


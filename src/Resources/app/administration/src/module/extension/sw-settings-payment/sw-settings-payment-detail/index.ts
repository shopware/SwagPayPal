import type * as PayPal from 'src/types';
import template from './sw-settings-payment-detail.html.twig';
import './sw-settings-payment-detail.scss';

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPayPalApiCredentialsService',
    ],

    data(): {
        merchantIntegrations: $TSFixMe;
        capabilities: PayPal.Setting<'merchant_information'>['capabilities'];
    } {
        return {
            /**
             * @deprecated tag:v10.0.0 - Will be removed, use this.capabilities instead
             */
            merchantIntegrations: [],
            capabilities: {},
        };
    },

    computed: {
        disableActiveSwitch(): boolean {
            // @ts-expect-error - paymentMethod is from extended component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-argument, @typescript-eslint/no-unsafe-member-access -- paymentMethod is from extended component
            return !this.acl.can('payment.editor') || this.needsOnboarding(this.paymentMethod.id);
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.fetchMerchantIntegrations();
            this.fetchMerchantCapabilities();
        },

        needsOnboarding(id: string): boolean {
            const capabilityIds = Object.keys(this.capabilities);

            if (!capabilityIds.includes(id)) {
                return false;
            }

            return this.capabilities[id].toUpperCase() === 'INACTIVE';
        },

        /**
         * @deprecated tag:v10.0.0 - Will be removed, use this.fetchMerchantCapabilities instead
         */
        fetchMerchantIntegrations() {
            this.SwagPayPalApiCredentialsService
                .getMerchantInformation()
                .then((response) => {
                    this.merchantIntegrations = response.merchantIntegrations ?? [];
                });
        },

        async fetchMerchantCapabilities() {
            const merchantInformation = await this.SwagPayPalApiCredentialsService.getMerchantInformation();

            this.capabilities = merchantInformation.capabilities ?? {};
        },
    },
});


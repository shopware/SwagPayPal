import type * as PayPal from 'SwagPayPal/types';

type State = {
    salesChannel: string | null;
    allMerchantInformations: Record<string, PayPal.Setting<'merchant_information'>>;
};

const store = Shopware.Store.register({
    id: 'swagPayPalMerchantInformation',

    state: (): State => ({
        salesChannel: null,
        allMerchantInformations: {},
    }),

    actions: {
        set(salesChannelId: string | null, merchantInformation: PayPal.Setting<'merchant_information'>) {
            this.allMerchantInformations[String(salesChannelId)] = merchantInformation;
        },

        has(salesChannelId: string | null): boolean {
            return this.allMerchantInformations.hasOwnProperty(String(salesChannelId));
        },

        delete(salesChannelId: string | null) {
            delete this.allMerchantInformations[String(salesChannelId)];
        },
    },

    getters: {
        isLoading(): boolean {
            return !this.allMerchantInformations.hasOwnProperty(String(this.salesChannel));
        },

        actual(): PayPal.Setting<'merchant_information'> {
            return this.allMerchantInformations[String(this.salesChannel)] ?? {
                merchantIntegrations: null,
                capabilities: {},
            };
        },

        products(): PayPal.V1<'merchant_integrations'>['products'] {
            return this.actual.merchantIntegrations?.products ?? [];
        },

        capabilities(): PayPal.Setting<'merchant_information'>['capabilities'] {
            return this.actual.capabilities;
        },

        merchantCapabilities(): NonNullable<PayPal.V1<'merchant_integrations'>['capabilities']> {
            return this.actual.merchantIntegrations?.capabilities ?? [];
        },

        canVault(): boolean {
            return this.merchantCapabilities.some(
                (capability) => capability.name === 'PAYPAL_WALLET_VAULTING_ADVANCED' && capability.status === 'ACTIVE',
            );
        },

        canPPCP(): boolean {
            return this.merchantCapabilities.some(
                (capability) => capability.name === 'PAYPAL_CHECKOUT' && capability.status === 'ACTIVE',
            );
        },
    },
});

export type MerchantInformationStore = ReturnType<typeof store>;
export default store;

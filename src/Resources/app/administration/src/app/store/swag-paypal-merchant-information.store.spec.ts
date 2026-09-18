import MIFixture from './merchant-information.fixture';
import './swag-paypal-merchant-information.store';

describe('swag-paypal-merchant-information.store', () => {
    const store = Shopware.Store.get('swagPayPalMerchantInformation');

    it('shoud be a pinia store', () => {
        expect(store.$id).toBe('swagPayPalMerchantInformation');
    });

    it('should have correct default state', () => {
        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allMerchantInformations).toStrictEqual({});

        // actions
        expect(store.has(null)).toBe(false);

        // getters
        expect(store.isLoading).toBe(true);
        expect(store.actual).toStrictEqual({
            merchantIntegrations: null,
            capabilities: {},
            sdkV6Eligible: null,
        });
        expect(store.products).toStrictEqual([]);
        expect(store.capabilities).toStrictEqual({});
        expect(store.merchantCapabilities).toStrictEqual([]);
        expect(store.canVault).toBe(false);
        expect(store.canPPCP).toBe(false);
        expect(store.canSdkV6).toBe(false);
    });

    it('should have correct root state', () => {
        store.set(null, MIFixture.Default);

        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allMerchantInformations).toStrictEqual({ null: MIFixture.Default });

        // actions
        expect(store.has(null)).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.actual).toStrictEqual(MIFixture.Default);
        expect(store.products).toStrictEqual(MIFixture.Default.merchantIntegrations.products);
        expect(store.capabilities).toStrictEqual(MIFixture.Default.capabilities);
        expect(store.merchantCapabilities).toStrictEqual(MIFixture.Default.merchantIntegrations.capabilities);
        expect(store.canVault).toBe(true);
        expect(store.canPPCP).toBe(true);
        expect(store.canSdkV6).toBe(true);
    });

    it('should have correct non-vault state', () => {
        store.set(null, MIFixture.NonVault);

        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allMerchantInformations).toStrictEqual({ null: MIFixture.NonVault });

        // actions
        expect(store.has(null)).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.actual).toStrictEqual(MIFixture.NonVault);
        expect(store.products).toStrictEqual(MIFixture.NonVault.merchantIntegrations.products);
        expect(store.capabilities).toStrictEqual(MIFixture.NonVault.capabilities);
        expect(store.merchantCapabilities).toStrictEqual(MIFixture.NonVault.merchantIntegrations.capabilities);
        expect(store.canVault).toBe(false);
        expect(store.canPPCP).toBe(true);
        expect(store.canSdkV6).toBe(true);
    });

    it('should have correct non-ppcp state', () => {
        store.set(null, MIFixture.NonPPCP);

        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allMerchantInformations).toStrictEqual({ null: MIFixture.NonPPCP });

        // actions
        expect(store.has(null)).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.actual).toStrictEqual(MIFixture.NonPPCP);
        expect(store.products).toStrictEqual(MIFixture.NonPPCP.merchantIntegrations.products);
        expect(store.capabilities).toStrictEqual(MIFixture.NonPPCP.capabilities);
        expect(store.merchantCapabilities).toStrictEqual(MIFixture.NonPPCP.merchantIntegrations.capabilities);
        expect(store.canVault).toBe(true);
        expect(store.canPPCP).toBe(false);
        expect(store.canSdkV6).toBe(true);
    });

    it('should have correct not-logged-in state', () => {
        store.set(null, MIFixture.NotLoggedIn);

        // state
        expect(store.salesChannel).toBeNull();
        expect(store.allMerchantInformations).toStrictEqual({ null: MIFixture.NotLoggedIn });

        // actions
        expect(store.has(null)).toBe(true);

        // getters
        expect(store.isLoading).toBe(false);
        expect(store.actual).toStrictEqual(MIFixture.NotLoggedIn);
        expect(store.products).toStrictEqual([]);
        expect(store.capabilities).toStrictEqual(MIFixture.NotLoggedIn.capabilities);
        expect(store.merchantCapabilities).toStrictEqual([]);
        expect(store.canVault).toBe(false);
        expect(store.canPPCP).toBe(false);
        expect(store.canSdkV6).toBe(false);
    });

    it('should not allow sdk v6 when it is not activated in the PayPal account', () => {
        store.set(null, MIFixture.SdkV6Ineligible);

        expect(store.actual.sdkV6Eligible).toBe(false);
        expect(store.canSdkV6).toBe(false);
    });

    it('should not allow sdk v6 while the eligibility is unknown', () => {
        store.set(null, MIFixture.SdkV6Unknown);

        expect(store.actual.sdkV6Eligible).toBeNull();
        expect(store.canSdkV6).toBe(false);
    });
});

import { mount } from '@vue/test-utils';
import SwagPayPalOnboardingButton from '.';

Shopware.Component.register('swag-paypal-onboarding-button', Promise.resolve(SwagPayPalOnboardingButton));

async function loadScript(script: Element) {
    window.PAYPAL = {
        apps: {
            // @ts-expect-error - not fully implemented on purpose
            Signup: {
                setup: jest.fn(),
                render: jest.fn(),
            },
        },
    };

    script.dispatchEvent(new Event('load'));

    await flushPromises();
}

async function createWrapper() {
    const route: Record<string, unknown> = {
        path: '/sw/path',
        query: {},
    };

    return mount(
        await Shopware.Component.build('swag-paypal-onboarding-button') as typeof SwagPayPalOnboardingButton,
        {
            global: {
                mocks: {
                    $t: (key: string) => key,
                    $route: route,
                    $router: {
                        replace: jest.fn((params: Record<string, unknown>) => {
                            route.query = params.query;
                        }),
                    },
                },
                provide: {
                    SwagPayPalSettingsService: {
                        getApiCredentials: jest.fn(),
                    },
                },
                stubs: {
                    'sw-loader': true,
                },
            },
        },
    );
}

describe('swag-paypal-onboarding-button', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should initialize component', async () => {
        global.activeAclRoles = ['swag_paypal.editor'];
        const wrapper = await createWrapper();

        const script = document.head.querySelector('#paypal-js');
        expect(script).toBeTruthy();
        await loadScript(script!);

        expect(typeof window.PAYPAL!.apps.Signup.render).toBe('function');
        expect(window.PAYPAL!.apps.Signup.setup).not.toHaveBeenCalled();

        const renderSpy = jest.spyOn(wrapper.vm, 'renderPayPalButton');

        wrapper.vm.loadPayPalScript();

        expect(renderSpy).not.toHaveBeenCalled();
        expect(window.PAYPAL!.apps.Signup.setup).toHaveBeenCalled();
    });

    it('should set config', async () => {
        const wrapper = await createWrapper();
        const store = Shopware.Store.get('swagPayPalSettings');

        store.setConfig(null, {});
        wrapper.setProps({ mode: 'live' });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.suffix).toBe('');
        wrapper.vm.setConfig('client-id', 'client-secret', 'payer-id');

        expect(store.allConfigs).toStrictEqual({
            null: {
                'SwagPayPal.settings.clientId': 'client-id',
                'SwagPayPal.settings.clientSecret': 'client-secret',
                'SwagPayPal.settings.merchantPayerId': 'payer-id',
                'SwagPayPal.settings.sandbox': false,
            },
        });

        store.setConfig(null, {});
        wrapper.setProps({ mode: 'sandbox' });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.suffix).toBe('Sandbox');
        wrapper.vm.setConfig('client-id', 'client-secret', 'payer-id');

        expect(store.allConfigs).toStrictEqual({
            null: {
                'SwagPayPal.settings.clientIdSandbox': 'client-id',
                'SwagPayPal.settings.clientSecretSandbox': 'client-secret',
                'SwagPayPal.settings.merchantPayerIdSandbox': 'payer-id',
                'SwagPayPal.settings.sandbox': true,
            },
        });
    });

    it('should have right onboarding url', async () => {
        const wrapper = await createWrapper();

        const sharedParams = [
            ['channelId', 'partner'],
            ['product', 'ppcp'],
            ['secondaryProducts', 'advanced_vaulting,PAYMENT_METHODS'],
            ['capabilities', 'APPLE_PAY,GOOGLE_PAY,PAY_UPON_INVOICE,PAYPAL_WALLET_VAULTING_ADVANCED'],
            ['integrationType', 'FO'],
            ['features', 'PAYMENT,REFUND,READ_SELLER_DISPUTE,UPDATE_SELLER_DISPUTE,ADVANCED_TRANSACTIONS_SEARCH,ACCESS_MERCHANT_INFORMATION,TRACKING_SHIPMENT_READWRITE,VAULT,BILLING_AGREEMENT'],
            ['displayMode', 'minibrowser'],
            ['partnerLogoUrl', 'https://assets.shopware.com/media/logos/shopware_logo_blue.svg'],
        ];

        wrapper.vm.live.sellerNonce = 'live-nonce';
        wrapper.vm.sandbox.sellerNonce = 'sandbox-nonce';

        wrapper.setProps({ mode: 'live' });
        await wrapper.vm.$nextTick();

        const liveUrl = new URL(wrapper.vm.onboardingUrl);
        expect(liveUrl.origin).toBe('https://www.paypal.com');
        expect(liveUrl.pathname).toBe('/bizsignup/partner/entry');
        expect(Array.from(liveUrl.searchParams)).toStrictEqual([
            ...sharedParams,
            ['partnerId', 'DYKPBPEAW5JNA'],
            ['partnerClientId', 'AR1aQ13lHxH1c6b3CDd8wSY6SWad2Lt5fv5WkNIZg-qChBoGNfHr2kT180otUmvE_xXtwkgahXUBBurW'],
            ['sellerNonce', 'live-nonce'],
            ['returnToPartnerUrl', 'http://localhost/#/sw/path?ppOnboarding=live'],
        ]);

        wrapper.setProps({ mode: 'sandbox' });
        await wrapper.vm.$nextTick();

        const sandboxUrl = new URL(wrapper.vm.onboardingUrl);
        expect(sandboxUrl.origin).toBe('https://www.sandbox.paypal.com');
        expect(sandboxUrl.pathname).toBe('/bizsignup/partner/entry');
        expect(Array.from(sandboxUrl.searchParams)).toStrictEqual([
            ...sharedParams,
            ['partnerId', '45KXQA7PULGAG'],
            ['partnerClientId', 'AQ9g8qMYHpE8s028VCq_GO3Roy9pjeqGDjKTkR_sxzX0FtncBb3QUWbFtoQMtdpe2lG9NpnDT419dK8s'],
            ['sellerNonce', 'sandbox-nonce'],
            ['returnToPartnerUrl', 'http://localhost/#/sw/path?ppOnboarding=sandbox'],
        ]);
    });

    it('should complete onboarding', async () => {
        const wrapper = await createWrapper();

        const store = Shopware.Store.get('swagPayPalSettings');

        store.setConfig(null, {});
        wrapper.vm.$router.replace({
            query: {
                ppOnboarding: 'sandbox',
                merchantIdInPayPal: 'payer-id-sandbox',
            },
        });

        wrapper.vm.onMounted();

        expect(wrapper.vm.$route.query).toStrictEqual({});
        expect(store.allConfigs).toStrictEqual({
            null: {
                'SwagPayPal.settings.merchantPayerIdSandbox': 'payer-id-sandbox',
            },
        });

        store.setConfig(null, {});
        wrapper.vm.$router.replace({
            query: {
                ppOnboarding: 'live',
                merchantIdInPayPal: 'payer-id-live',
            },
        });

        wrapper.vm.onMounted();

        expect(wrapper.vm.$route.query).toStrictEqual({});
        expect(store.allConfigs).toStrictEqual({
            null: {
                'SwagPayPal.settings.merchantPayerId': 'payer-id-live',
            },
        });

        store.setConfig(null, {});
        wrapper.vm.$router.replace({
            query: {
                ppOnboarding: 'sdf-live',
                merchantIdInPayPal: 'merchant-id-live',
            },
        });

        wrapper.vm.onMounted();

        expect(wrapper.vm.$route.query).toStrictEqual({});
        expect(store.allConfigs).toStrictEqual({ null: {} });
    });

    it('should be able to change type after creation', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.suffix).toBe('');

        wrapper.vm.type = 'sandbox';

        expect(wrapper.vm.suffix).toBe('Sandbox');

        wrapper.vm.type = 'live';

        expect(wrapper.vm.suffix).toBe('');

        wrapper.setProps({ mode: 'sandbox' });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.suffix).toBe('Sandbox');
    });
});

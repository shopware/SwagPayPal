import { shallowMount } from '@vue/test-utils';
import 'src/app/component/utils/sw-inherit-wrapper';
import 'src/app/component/base/sw-card';
import 'src/app/component/form/sw-switch-field';
import 'src/app/component/form/sw-checkbox-field';
import 'src/app/mixin/notification.mixin';
import 'SwagPayPal/module/swag-paypal/components/swag-paypal-vaulting';

Shopware.Component.register('swag-paypal-vaulting', () => import('.'));

const onboardingCallbackLive = 'onboardingCallbackLive';
const onboardingCallbackSandbox = 'onboardingUrlSandbox';

async function createWrapper(customOptions = {}) {
    const options = {
        mocks: {
            $tc: (key) => key,
        },
        provide: {
            acl: {
                can: () => true,
            },
            SwagPayPalApiCredentialsService: {
                getMerchantInformation: () => Promise.resolve({ capabilities: [] }),
            },
        },
        components: {
            'sw-inherit-wrapper': {
                template: '<div class="sw-inherit-wrapper"><slot name="content"></slot></div>',
            },
            'sw-card': await Shopware.Component.build('sw-card'),
            'sw-switch-field': await Shopware.Component.build('sw-switch-field'),
            'sw-checkbox-field': await Shopware.Component.build('sw-checkbox-field'),
        },
        data() {
            return {
                onboardingUrlLive: onboardingCallbackLive,
                onboardingUrlSandbox: onboardingCallbackSandbox,
                requestParams: {
                    secondaryProducts: 'something',
                    capabilities: 'else',
                    features: [],
                },
            };
        },
        propsData: {
            actualConfigData: {
                'SwagPayPal.settings.sandbox': true,
                'SwagPayPal.settings.vaultingEnabled': true,
                'SwagPayPal.settings.vaultingEnableAlways': true,
            },
            allConfigs: { null: {} },
            selectedSalesChannelId: 'SALES_CHANNEL',
            isSaveSuccessful: false,
        },
    };

    return shallowMount(await Shopware.Component.build('swag-paypal-vaulting'), {
        ...options,
        ...customOptions,
    });
}

describe('Paypal Vaulting Component', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        await new Promise(process.nextTick);

        expect(wrapper.vm).toBeTruthy();
    });

    it('should set canHandleVaulting state correctly', async () => {
        const wrapper = await createWrapper({
            provide: {
                acl: {
                    can: () => true,
                },
                SwagPayPalApiCredentialsService: {
                    getMerchantInformation: () => Promise.resolve({
                        capabilities: [],
                        merchantIntegrations: {
                            capabilities: [
                                { name: 'PAYPAL_WALLET_VAULTING_ADVANCED', status: 'ACTIVE' },
                            ],
                        },
                    }),
                },
            },
        });

        await flushPromises();

        expect(wrapper.vm.canHandleVaulting).toBe(true);
    });

    it('should render onboarding buttons', async () => {
        const wrapper = await createWrapper();

        await new Promise(process.nextTick);

        const liveButton = await wrapper.find("a[data-paypal-onboard-complete='onboardingCallbackLive']");
        const sandboxButton = await wrapper.find("a[data-paypal-onboard-complete='onboardingCallbackSandbox']");

        expect(liveButton.exists()).toBe(true);
        expect(sandboxButton.exists()).toBe(true);
    });

    it('should link to the live onboarding guide', async () => {
        const wrapper = await createWrapper();

        await new Promise(process.nextTick);

        const liveButton = await wrapper.find("a[data-paypal-onboard-complete='onboardingCallbackLive']");

        expect(liveButton.attributes('href')).toBe(onboardingCallbackLive);
    });

    it('should link to the sandbox onboarding guide', async () => {
        const wrapper = await createWrapper();

        await new Promise(process.nextTick);

        const sandboxButton = await wrapper.find("a[data-paypal-onboard-complete='onboardingCallbackSandbox']");

        expect(sandboxButton.attributes('href')).toBe(onboardingCallbackSandbox);
    });
});

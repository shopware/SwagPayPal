import { mount } from '@vue/test-utils';
import 'SwagPayPal/module/swag-paypal/components/swag-paypal-checkout';

Shopware.Component.register('swag-paypal-checkout', () => import('.'));

const onboardingCallbackLive = 'onboardingCallbackLive';
const onboardingCallbackSandbox = 'onboardingUrlSandbox';

async function createWrapper(customOptions = {}) {
    const options = {
        global: {
            mocks: {
                $tc: (key) => key,
            },
            provide: {
                acl: {
                    can: () => true,
                },
                repositoryFactory: {
                    create: () => ({ search: () => Promise.resolve([]) }),
                },
                SwagPayPalApiCredentialsService: {
                    getMerchantInformation: () => Promise.resolve({ capabilities: [] }),
                },
            },
            stubs: {
                'sw-icon': true,
                'swag-paypal-inherit-wrapper': true,
                'sw-button-process': true,
                'sw-container': await wrapTestComponent('sw-container', { sync: true }),
                'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                'sw-card-deprecated': await wrapTestComponent('sw-card-deprecated', { sync: true }),
                'sw-alert': await wrapTestComponent('sw-alert', { sync: true }),
            },
        },
        data() {
            return {
                onboardingUrlLive: onboardingCallbackLive,
                onboardingUrlSandbox: onboardingCallbackSandbox,
            };
        },
        props: {
            actualConfigData: {},
            allConfigs: {},
            clientIdFilled: true,
            clientSecretFilled: true,
            clientIdSandboxFilled: true,
            clientSecretSandboxFilled: true,
            isLoading: true,
        },
    };

    return mount(await Shopware.Component.build('swag-paypal-checkout'), {
        ...options,
        ...customOptions,
    });
}

/**
 * @type {Wrapper}
 */
let wrapper;

describe('Paypal Configuration Component', () => {
    it('should link to the live onboarding guide', async () => {
        wrapper = await createWrapper();

        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackLive']").attributes('href'))
            .toBe(onboardingCallbackLive);
    });

    it('should link to the sandbox onboarding guide', async () => {
        wrapper = await createWrapper({
            propsData: {
                actualConfigData: {
                    'SwagPayPal.settings.sandbox': true,
                },
                allConfigs: {},
                clientIdFilled: true,
                clientSecretFilled: true,
                clientIdSandboxFilled: true,
                clientSecretSandboxFilled: true,
                isLoading: true,
            },
        });

        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackSandbox']").attributes('href'))
            .toBe(onboardingCallbackSandbox);
    });
});

// eslint-disable-next-line import/no-unresolved
import { shallowMount } from '@vue/test-utils';
/* eslint-disable import/no-extraneous-dependencies */
import 'src/app/component/base/sw-card';
import 'src/app/component/base/sw-container';
import 'src/app/component/base/sw-icon';
import 'src/app/component/base/sw-alert';
/* eslint-enable import/no-extraneous-dependencies */
import '../../../../src/module/swag-paypal/components/swag-paypal-checkout';

const onboardingCallbackLive = 'onboardingCallbackLive';
const onboardingCallbackSandbox = 'onboardingUrlSandbox';

function createWrapper(customOptions = {}) {
    const options = {
        mocks: {
            $tc: (key) => key
        },
        provide: {
            acl: {
                can: () => true
            },
            repositoryFactory: {
                create: () => ({ search: () => Promise.resolve() })
            },
            SwagPayPalApiCredentialsService: {},
        },
        stubs: ['sw-icon', 'sw-inherit-wrapper', 'sw-button-process'],
        components: {
            'sw-container': Shopware.Component.build('sw-container'),
            'sw-card': Shopware.Component.build('sw-card'),
            'sw-alert': Shopware.Component.build('sw-alert'),
        },
        filters: {
            asset: null,
        },
        data() {
            return {
                onboardingUrlLive: onboardingCallbackLive,
                onboardingUrlSandbox: onboardingCallbackSandbox
            };
        },
        propsData: {
            actualConfigData: {},
            allConfigs: {},
            clientIdFilled: true,
            clientSecretFilled: true,
            clientIdSandboxFilled: true,
            clientSecretSandboxFilled: true,
            isLoading: true
        }
    };

    return shallowMount(Shopware.Component.build('swag-paypal-checkout'), {
        ...options,
        ...customOptions,
    });
}

/**
 * @type {Wrapper}
 */
let wrapper;

describe('Paypal Configuration Component', () => {
    it('should link to the live onboarding guide', () => {
        wrapper = createWrapper();

        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackLive']").attributes('href')).toBe(onboardingCallbackLive);
    });

    it('should link to the sandbox onboarding guide', () => {
        wrapper = createWrapper({
            propsData: {
                actualConfigData: {
                    'SwagPayPal.settings.sandbox': true,
                },
                allConfigs: {},
                clientIdFilled: true,
                clientSecretFilled: true,
                clientIdSandboxFilled: true,
                clientSecretSandboxFilled: true,
                isLoading: true
            }
        });

        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackSandbox']").attributes('href')).toBe(onboardingCallbackSandbox);
    });
});

// eslint-disable-next-line import/no-unresolved
import { shallowMount } from '@vue/test-utils';
import 'src/app/component/base/sw-card';
import 'src/app/component/base/sw-container';
import 'src/app/component/base/sw-icon';
import '../../../../src/module/swag-paypal/components/swag-paypal-credentials';

const onboardingCallbackLive = 'onboardingCallbackLive';
const onboardingCallbackSandbox = 'onboardingUrlSandbox';

function createWrapper() {
    return shallowMount(Shopware.Component.build('swag-paypal-credentials'), {
        mocks: {
            $tc: (key) => key
        },
        provide: {
            acl: {
                can: () => true
            }
        },
        stubs: ['sw-icon', 'sw-inherit-wrapper', 'sw-button-process'],
        components: {
            'sw-container': Shopware.Component.build('sw-container'),
            'sw-card': Shopware.Component.build('sw-card')
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
    });
}

/**
 * @type {Wrapper}
 */
let wrapper;

describe('Paypal Configuration Component', () => {
    beforeEach(() => {
        wrapper = createWrapper();
    });

    it('should link to the live onboarding guide', () => {
        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackLive']").attributes('href')).toBe(onboardingCallbackLive);
    });

    it('should link to the sandbox onboarding guide', () => {
        expect(wrapper.find("[data-paypal-onboard-complete='onboardingCallbackSandbox']").attributes('href')).toBe(onboardingCallbackSandbox);
    });
});

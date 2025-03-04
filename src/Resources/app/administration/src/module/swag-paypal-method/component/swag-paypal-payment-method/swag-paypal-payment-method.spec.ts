import { mount } from '@vue/test-utils';
import SwagPayPalPaymentMethod from '.';
import MIFixture from '../../../../app/store/merchant-information.fixture';

Shopware.Component.register('swag-paypal-payment-method', Promise.resolve(SwagPayPalPaymentMethod));

async function createWrapper(
    paymentMethod: Partial<TEntity<'payment_method'>> = {},
    availableTrans: string[] = [],
) {
    return mount(
        await Shopware.Component.build('swag-paypal-payment-method') as typeof SwagPayPalPaymentMethod,
        {
            global: {
                stubs: {
                    'sw-help-text': await wrapTestComponent('sw-help-text', { sync: true }),
                    'sw-label': await wrapTestComponent('sw-label', { sync: true }),
                    'sw-color-badge': await wrapTestComponent('sw-color-badge', { sync: true }),
                    'sw-switch-field': await wrapTestComponent('sw-switch-field', { sync: true }),
                    'sw-skeleton-bar': await wrapTestComponent('sw-skeleton-bar', { sync: true }),
                    'sw-skeleton-bar-deprecated': await wrapTestComponent('sw-skeleton-bar-deprecated', { sync: true }),
                    'swag-paypal-method-domain-association': true,
                    'router-link': true,
                },
                mocks: {
                    $te: (key: string) => availableTrans.includes(key),
                },
            },
            props: {
                paymentMethod: Shopware.Utils.object.merge({
                    id: 'some-payment-method-id',
                    active: true,
                    translated: { name: 'PayPal' },
                    formattedHandlerIdentifier: 'handler_swag_paypal',
                }, paymentMethod) as TEntity<'payment_method'>,
            },
            provide: {
                repositoryFactory: {},
            },
        },
    );
}

describe('swag-paypal-payment-method', () => {
    const store = Shopware.Store.get('swagPayPalMerchantInformation');

    beforeEach(() => {
        store.set(null, MIFixture.NotLoggedIn);
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have correct state based on capability', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.onboardingStatus).toBe('inactive');
        expect(wrapper.vm.identifier).toBe('paypal');
        expect(wrapper.vm.isPui).toBe(false);
        expect(wrapper.vm.paymentMethodToggleDisabled).toBe(false);
        expect(wrapper.vm.showEditLink).toBe(false);
        expect(wrapper.vm.statusBadgeVariant).toBe('neutral');
        expect(wrapper.vm.onboardingStatusText).toContain('onboardingStatusText.inactive');
        expect(wrapper.vm.onboardingStatusTooltip).toBeNull();
        expect(wrapper.vm.availabilityToolTip).toBeNull();

        wrapper.vm.paymentMethod.active = false;
        expect(wrapper.vm.paymentMethodToggleDisabled).toBe(true);
        expect(wrapper.vm.showEditLink).toBe(false);
        wrapper.vm.paymentMethod.active = true;

        store.set(null, MIFixture.Default);

        expect(wrapper.vm.onboardingStatus).toBe('active');
        expect(wrapper.vm.paymentMethodToggleDisabled).toBe(false);
        expect(wrapper.vm.showEditLink).toBe(true);
        expect(wrapper.vm.statusBadgeVariant).toBe('success');
        expect(wrapper.vm.onboardingStatusText).toContain('onboardingStatusText.active');
        expect(wrapper.vm.onboardingStatusTooltip).toBeNull();
        expect(wrapper.vm.availabilityToolTip).toBeNull();

        wrapper.vm.paymentMethod.active = false;
        expect(wrapper.vm.paymentMethodToggleDisabled).toBe(false);
        expect(wrapper.vm.showEditLink).toBe(true);
        wrapper.vm.paymentMethod.active = true;
    });

    it('should translate depending on snippet availability', async () => {
        let wrapper = await createWrapper();

        expect(wrapper.vm.onboardingStatusTooltip).toBeNull();
        expect(wrapper.vm.availabilityToolTip).toBeNull();

        wrapper = await createWrapper({}, [
            'swag-paypal-method.onboardingStatusTooltip.inactive',
        ]);

        expect(wrapper.vm.onboardingStatusTooltip).toContain('onboardingStatusTooltip.inactive');
        expect(wrapper.vm.availabilityToolTip).toBeNull();

        wrapper = await createWrapper({}, [
            'swag-paypal-method.onboardingStatusTooltip.inactive',
            'swag-paypal-method.availabilityToolTip.paypal',
        ]);

        expect(wrapper.vm.onboardingStatusTooltip).toContain('onboardingStatusTooltip.inactive');
        expect(wrapper.vm.availabilityToolTip).toContain('availabilityToolTip.paypal');
    });

    it('should have correct loading state', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.find('.sw-skeleton-bar').exists()).toBe(false);
        expect(wrapper.find('.swag-paypal-payment-method__dynamic').exists()).toBe(true);

        store.delete(null);
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.sw-skeleton-bar').exists()).toBe(true);
        expect(wrapper.find('.swag-paypal-payment-method__dynamic').exists()).toBe(false);
    });

    it('should toggle payment method active state', async () => {
        global.activeAclRoles = ['swag_paypal.editor'];
        const wrapper = await createWrapper({ active: true });

        const switchField = wrapper.find('input[type="checkbox"]');
        expect(switchField.exists()).toBe(true);

        await switchField.setValue(true);
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted('update:active')).toBeFalsy();

        await switchField.setValue(false);
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted('update:active')).toBeTruthy();
    });
});

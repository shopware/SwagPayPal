import { mount } from '@vue/test-utils';
import SwagPayPalMethodMerchantInformation from '.';
import MIFixture from 'SwagPayPal/app/store/merchant-information.fixture';
import SettingsFixture from 'SwagPayPal/app/store/settings.fixture';
import SwagPayPalSetting from 'SwagPayPal/app/component/swag-paypal-setting';

Shopware.Component.register('swag-paypal-setting', Promise.resolve(SwagPayPalSetting));
Shopware.Component.register('swag-paypal-method-merchant-information', Promise.resolve(SwagPayPalMethodMerchantInformation));

async function createWrapper(active: boolean = true) {
    return mount(
        await Shopware.Component.build('swag-paypal-method-merchant-information') as typeof SwagPayPalMethodMerchantInformation,
        {
            global: {
                stubs: {
                    'sw-alert': await wrapTestComponent('sw-alert', { sync: true }),
                    'sw-alert-deprecated': await wrapTestComponent('sw-alert-deprecated', { sync: true }),
                    'sw-external-link': await wrapTestComponent('sw-external-link', { sync: true }),
                    'sw-external-link-deprecated': await wrapTestComponent('sw-external-link-deprecated', { sync: true }),
                    'swag-paypal-setting': await Shopware.Component.build('swag-paypal-setting'),
                },
            },
            props: {
                paymentMethod: { active } as TEntity<'payment_method'>,
            },
        },
    );
}

describe('swag-paypal-method-merchant-information', () => {
    beforeEach(() => {
        Shopware.Store.get('swagPayPalMerchantInformation').set(null, MIFixture.Default);
        Shopware.Store.get('swagPayPalSettings').setConfig(null, SettingsFixture.WithCredentials);
    });

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should set disabled of sandbox toggle', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.merchantEmail).toBe('test@example.com');
        expect(wrapper.vm.sandboxToggleDisabled).toBe(true);

        wrapper.vm.settingsStore.setConfig(null, SettingsFixture.WithSandboxCredentials);
        expect(wrapper.vm.sandboxToggleDisabled).toBe(false);

        wrapper.vm.settingsStore.set('SwagPayPal.settings.sandbox', true);
        expect(wrapper.vm.sandboxToggleDisabled).toBe(true);

        wrapper.vm.settingsStore.setConfig(null, {
            ...SettingsFixture.WithCredentials,
            ...SettingsFixture.WithSandboxCredentials,
        });

        expect(wrapper.vm.sandboxToggleDisabled).toBe(false);
    });

    it('should trigger save on sandbox toggle', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.settingsStore.setConfig(null, {
            ...SettingsFixture.WithCredentials,
            ...SettingsFixture.WithSandboxCredentials,
        });

        const setting = wrapper.findComponent<typeof SwagPayPalSetting>('.swag-paypal-setting');
        expect(setting.exists()).toBe(true);
        setting.vm.$emit('update:value');

        expect(wrapper.emitted('save')).toBeTruthy();
    });
});

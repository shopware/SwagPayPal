import { mount } from '@vue/test-utils';
import SwagPayPalSettingsStorefront from '.';
import { SYSTEM_CONFIGS } from '../../../../constant/swag-paypal-settings.constant';
import SettingsFixture from '../../../../app/store/settings.fixture';
import type SwagPayPalSetting from 'SwagPayPal/app/component/swag-paypal-setting';

Shopware.Component.register('swag-paypal-settings-storefront', Promise.resolve(SwagPayPalSettingsStorefront));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-storefront') as typeof SwagPayPalSettingsStorefront,
        {
            global: {
                stubs: {
                    'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                    'swag-paypal-setting': {
                        name: 'swag-paypal-setting',
                        props: ['path'],
                        template: '<div class="swag-paypal-setting"></div>',
                    },
                    'swag-paypal-settings-locale-select': true,
                },
                provide: {
                    systemConfigApiService: { getValues: () => false },
                    repositoryFactory: {
                        create: () => ({
                            search: jest.fn(() => Promise.resolve([])),
                        }),
                    },
                },
            },
        },
    );
}

describe('swag-paypal-settings-storefront', () => {
    const store = Shopware.Store.get('swagPayPalSettings');

    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have settings cards', async () => {
        const wrapper = await createWrapper();

        const cardClasses = wrapper
            .findAll('.mt-card')
            .map((el) => el.classes())
            .flat()
            .filter((cl) => cl.startsWith('swag-paypal'));

        expect(cardClasses).toEqual([
            'swag-paypal-settings-express',
            'swag-paypal-settings-installment',
            'swag-paypal-settings-spb',
        ]);
    });

    it('should have settings', async () => {
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = components.map((el) => el.props().path);

        expect(settings).toEqual([
            'SwagPayPal.settings.ecsDetailEnabled',
            'SwagPayPal.settings.ecsCartEnabled',
            'SwagPayPal.settings.ecsOffCanvasEnabled',
            'SwagPayPal.settings.ecsLoginEnabled',
            'SwagPayPal.settings.ecsListingEnabled',
            'SwagPayPal.settings.ecsButtonColor',
            'SwagPayPal.settings.ecsButtonShape',
            'SwagPayPal.settings.ecsButtonLanguageIso',
            'SwagPayPal.settings.ecsShowPayLater',
            'SwagPayPal.settings.installmentBannerDetailPageEnabled',
            'SwagPayPal.settings.installmentBannerCartEnabled',
            'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled',
            'SwagPayPal.settings.installmentBannerLoginPageEnabled',
            'SwagPayPal.settings.installmentBannerFooterEnabled',
            'SwagPayPal.settings.spbCheckoutEnabled',
            'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled',
            'SwagPayPal.settings.spbShowPayLater',
            'SwagPayPal.settings.spbButtonColor',
            'SwagPayPal.settings.spbButtonShape',
            'SwagPayPal.settings.spbButtonLanguageIso',
        ]);
    });

    it('should disable ecs fields based on ecsSettingsDisabled', async () => {
        store.setConfig(null, SettingsFixture.Default);
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = Object.fromEntries(components.map((el) => [el.props().path, el]));

        const disabledSettings = [
            'SwagPayPal.settings.ecsButtonColor',
            'SwagPayPal.settings.ecsButtonShape',
            'SwagPayPal.settings.ecsButtonLanguageIso',
            'SwagPayPal.settings.ecsShowPayLater',
        ];

        // enable all
        SYSTEM_CONFIGS.filter((setting) => setting.startsWith('SwagPayPal.settings.ecs')).forEach((setting) => store.set(setting, true));
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.ecsSettingsDisabled).toBe(false);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(4).fill(false));

        // disable all
        SYSTEM_CONFIGS.filter((setting) => setting.startsWith('SwagPayPal.settings.ecs')).forEach((setting) => store.set(setting, false));
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.ecsSettingsDisabled).toBe(true);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(4).fill(true));
    });

    it('should disable spb fields based on spbCheckoutEnabled', async () => {
        store.setConfig(null, SettingsFixture.Default);
        const wrapper = await createWrapper();

        const components = wrapper.findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' });
        const settings = Object.fromEntries(components.map((el) => [el.props().path, el]));

        const disabledSettings = [
            'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled',
            'SwagPayPal.settings.spbShowPayLater',
            'SwagPayPal.settings.spbButtonColor',
            'SwagPayPal.settings.spbButtonShape',
            'SwagPayPal.settings.spbButtonLanguageIso',
        ];

        store.set('SwagPayPal.settings.spbCheckoutEnabled', true);
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.sbpSettingsDisabled).toBe(false);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(5).fill(false));

        store.set('SwagPayPal.settings.spbCheckoutEnabled', false);
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.sbpSettingsDisabled).toBe(true);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(5).fill(true));
    });
});

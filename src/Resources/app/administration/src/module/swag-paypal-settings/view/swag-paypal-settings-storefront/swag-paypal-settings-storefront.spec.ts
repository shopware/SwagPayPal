import { mount } from '@vue/test-utils';
import SwagPayPalSettingsStorefront from '.';
import { SYSTEM_CONFIGS } from '../../../../constant/swag-paypal-settings.constant';
import SettingsFixture from '../../../../app/store/settings.fixture';
import type SwagPayPalSetting from 'SwagPayPal/app/component/swag-paypal-setting';
import EntityCollection from "@shopware-ag/meteor-admin-sdk/es/_internals/data/EntityCollection";
import Entity from "@shopware-ag/meteor-admin-sdk/es/_internals/data/Entity";

Shopware.Component.register('swag-paypal-settings-storefront', Promise.resolve(SwagPayPalSettingsStorefront));

async function createWrapper(systemConfigValues: EntityCollection<"system_config"> | null = null) {
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
                    'swag-paypal-settings-banner-preview': true,
                },
                provide: {
                    systemConfigApiService: { getValues: () => false },
                    repositoryFactory: {
                        create: () => ({
                            search: jest.fn(() => Promise.resolve(systemConfigValues ?? [])),
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
            'SwagPayPal.settings.ecsShippingCallbackEnabled',
            'SwagPayPal.settings.installmentBannerLogoType',
            'SwagPayPal.settings.installmentBannerTextColor',
            'SwagPayPal.settings.installmentBannerTextSize',
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

    it('should fetch config without selected sales channel', async () => {
        store.salesChannel = null;
        const wrapper = await createWrapper(
            new EntityCollection('', 'system_config', Shopware.Context.api, null, [
                new Entity('foo', 'system_config', {
                    id: 'foo',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'true',
                    createdAt: '',
                }),
            ]),
        );
        await flushPromises();

        expect(wrapper.vm.phoneRequiredConfig).toBe(true);
    });

    it('should fetch specific config with selected sales channel if true', async () => {
        store.salesChannel = 'foobar';
        const wrapper = await createWrapper(
            new EntityCollection('', 'system_config', Shopware.Context.api, null, [
                new Entity('foo', 'system_config', {
                    id: 'foo',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'true',
                    createdAt: '',
                    salesChannelId: 'foobar',
                }),
                new Entity('bar', 'system_config', {
                    id: 'bar',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'false',
                    createdAt: '',
                }),
            ]),
        );
        await flushPromises();

        expect(wrapper.vm.phoneRequiredConfig).toBe(true);
    });

    it('should fetch specific config with selected sales channel if false', async () => {
        store.salesChannel = 'foobar';
        const wrapper = await createWrapper(
            new EntityCollection('', 'system_config', Shopware.Context.api, null, [
                new Entity('foo', 'system_config', {
                    id: 'foo',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'false',
                    createdAt: '',
                    salesChannelId: 'foobar',
                }),
                new Entity('bar', 'system_config', {
                    id: 'bar',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'true',
                    createdAt: '',
                }),
            ]),
        );
        await flushPromises();

        expect(wrapper.vm.phoneRequiredConfig).toBe(false);
    });

    it('should fetch inherited config with selected sales channel', async () => {
        store.salesChannel = 'foobar';
        const wrapper = await createWrapper(
            new EntityCollection('', 'system_config', Shopware.Context.api, null, [
                new Entity('bar', 'system_config', {
                    id: 'bar',
                    configurationKey: 'core.loginRegistration.phoneNumberFieldRequired',
                    configurationValue: 'true',
                    createdAt: '',
                }),
            ]),
        );
        await flushPromises();

        expect(wrapper.vm.phoneRequiredConfig).toBe(true);
    });
});

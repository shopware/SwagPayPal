import { mount } from '@vue/test-utils';
import SwagPayPalSettingsStorefront from '.';
import { SYSTEM_CONFIGS } from '../../../../constant/swag-paypal-settings.constant';
import SettingsFixture from '../../../../app/store/settings.fixture';
import MIFixture from '../../../../app/store/merchant-information.fixture';
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
    const merchantStore = Shopware.Store.get('swagPayPalMerchantInformation');

    beforeEach(() => {
        merchantStore.$reset();
    });

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
            'swag-paypal-settings-sdk',
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
            'SwagPayPal.settings.sdkV6Enabled',
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
            'SwagPayPal.settings.spbAppSwitchEnabled',
            'SwagPayPal.settings.spbButtonColor',
            'SwagPayPal.settings.spbButtonShape',
            'SwagPayPal.settings.spbButtonLanguageIso',
        ]);
    });

    function findSdkV6Setting(wrapper: Awaited<ReturnType<typeof createWrapper>>) {
        return wrapper
            .findAllComponents<typeof SwagPayPalSetting>({ name: 'swag-paypal-setting' })
            .find((el) => el.props().path === 'SwagPayPal.settings.sdkV6Enabled');
    }

    function isSdkV6CardLoading(wrapper: Awaited<ReturnType<typeof createWrapper>>) {
        const card = wrapper.findAll('.mt-card').find((el) => el.classes().includes('swag-paypal-settings-sdk'));

        return card?.find('.mt-loader').exists();
    }

    it('should keep the sdk v6 setting disabled while the merchant information is loading', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.sdkV6SettingDisabled).toBe(true);
        // the reason for the disabled setting is not known yet
        expect(wrapper.vm.sdkV6Notice).toBeNull();
        expect(wrapper.find('.swag-paypal-settings-sdk__notice-banner').exists()).toBe(false);
    });

    it('should show the sdk v6 card as loading until the merchant information arrived', async () => {
        // the settings are there, only the merchant information is still missing
        store.setConfig(null, SettingsFixture.Default);
        const wrapper = await createWrapper();

        expect(isSdkV6CardLoading(wrapper)).toBe(true);

        merchantStore.set(null, MIFixture.Default);
        await wrapper.vm.$nextTick();

        expect(isSdkV6CardLoading(wrapper)).toBe(false);
    });

    it('should disable the sdk v6 setting when it is not activated in the PayPal account', async () => {
        merchantStore.set(null, MIFixture.SdkV6Ineligible);
        const wrapper = await createWrapper();

        expect(wrapper.vm.sdkV6SettingDisabled).toBe(true);
        expect(wrapper.vm.sdkV6Notice).toBe('swag-paypal-settings.sdk.ineligible');
        expect(findSdkV6Setting(wrapper)?.vm.$attrs.disabled).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-sdk__notice-banner').exists()).toBe(true);
    });

    it('should point at the api credentials while the eligibility is unknown', async () => {
        merchantStore.set(null, MIFixture.SdkV6Unknown);
        const wrapper = await createWrapper();

        expect(wrapper.vm.sdkV6SettingDisabled).toBe(true);
        // the sdk v6 may well be activated, so the opposite must not be claimed
        expect(wrapper.vm.sdkV6Notice).toBe('swag-paypal-settings.sdk.undetermined');
        expect(findSdkV6Setting(wrapper)?.vm.$attrs.disabled).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-sdk__notice-banner').exists()).toBe(true);
    });

    it('should enable the sdk v6 setting when it is activated in the PayPal account', async () => {
        merchantStore.set(null, MIFixture.Default);
        const wrapper = await createWrapper();

        expect(wrapper.vm.sdkV6SettingDisabled).toBe(false);
        expect(wrapper.vm.sdkV6Notice).toBeNull();
        expect(findSdkV6Setting(wrapper)?.vm.$attrs.disabled).toBe(false);
        expect(wrapper.find('.swag-paypal-settings-sdk__notice-banner').exists()).toBe(false);
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
            'SwagPayPal.settings.spbAppSwitchEnabled',
            'SwagPayPal.settings.spbButtonColor',
            'SwagPayPal.settings.spbButtonShape',
            'SwagPayPal.settings.spbButtonLanguageIso',
        ];

        store.set('SwagPayPal.settings.spbCheckoutEnabled', true);
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.sbpSettingsDisabled).toBe(false);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(6).fill(false));

        store.set('SwagPayPal.settings.spbCheckoutEnabled', false);
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.sbpSettingsDisabled).toBe(true);
        expect(disabledSettings.map((setting) => Boolean(settings[setting]?.vm.$attrs.disabled))).toStrictEqual(Array(6).fill(true));
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

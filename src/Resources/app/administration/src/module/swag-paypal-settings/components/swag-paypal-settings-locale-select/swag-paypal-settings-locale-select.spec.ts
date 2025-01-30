import { mount } from '@vue/test-utils';
import SwagPayPalSettingsLocaleSelect from '.';
import { LOCALES, type LOCALE } from 'SwagPayPal/constant/swag-paypal-settings.constant';

Shopware.Component.register('swag-paypal-settings-locale-select', Promise.resolve(SwagPayPalSettingsLocaleSelect));

async function createWrapper(value: LOCALE | undefined = undefined) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-locale-select') as typeof SwagPayPalSettingsLocaleSelect,
        {
            props: { value },
            global: {
                mocks: {
                    $t: (key: string) => key,
                    $te: () => true,
                },
                stubs: {
                    'sw-single-select': await wrapTestComponent('sw-single-select', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-settings-locale-select', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should update value', async () => {
        const wrapper = await createWrapper();

        const select = wrapper.findComponent<VueComponent>('.sw-single-select');
        expect(select.exists()).toBe(true);
        // eslint-disable-next-line @typescript-eslint/no-unsafe-call
        select.vm.$emit('update:value', 'de-DE');

        expect(wrapper.emitted('update:value')).toEqual([['de-DE']]);
    });

    it('should convert locale to option', async () => {
        const wrapper = await createWrapper();

        const option = wrapper.vm.toOption('de_DE');

        expect(option).toEqual({
            value: 'de_DE',
            dashed: 'de-DE',
            label: 'locale.de-DE',
        });
    });

    it('should have options', async () => {
        const wrapper = await createWrapper();

        const values = wrapper.vm.options.map((option) => option.value);

        expect(values).toEqual([null, ...LOCALES]);
    });

    it('should have invalid error', async () => {
        const wrapper = await createWrapper('invalid-locale' as LOCALE);

        expect(wrapper.vm.invalidError).toEqual({ detail: 'swag-paypal-settings-locale-select.invalid' });

        const values = wrapper.vm.options.map((option) => option.value);
        expect(values).toEqual(['invalid-locale', null, ...LOCALES]);
    });
});

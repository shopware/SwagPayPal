import { mount } from '@vue/test-utils';
import SwagPayPalSettingsDiagnosticsItem from '.';

Shopware.Component.register('swag-paypal-settings-diagnostics-item', Promise.resolve(SwagPayPalSettingsDiagnosticsItem));

const BLOCK = '.swag-paypal-settings-diagnostics-item';

async function createWrapper(
    props: Record<string, unknown> = {},
    slots: Record<string, string> = {},
) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-item') as typeof SwagPayPalSettingsDiagnosticsItem,
        {
            props: { status: 'info', title: 'Title', ...props },
            slots,
        },
    );
}

describe('swag-paypal-settings-diagnostics-item', () => {
    it('should fold the content below a summary line', async () => {
        const wrapper = await createWrapper({}, { default: '<p>Details</p>' });

        expect(wrapper.element.tagName).toBe('DETAILS');
        expect(wrapper.find(`${BLOCK}__summary`).element.tagName).toBe('SUMMARY');
        expect(wrapper.find(`${BLOCK}__title`).text()).toBe('Title');
        expect(wrapper.find(`${BLOCK}__chevron`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__content`).text()).toBe('Details');
    });

    it('should be a plain line without content', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.element.tagName).toBe('DIV');
        expect(wrapper.find(`${BLOCK}__summary`).element.tagName).toBe('DIV');
        expect(wrapper.find(`${BLOCK}__chevron`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__content`).exists()).toBe(false);
    });

    it.each([
        ['error', 'solid-times-circle'],
        ['warning', 'solid-exclamation-triangle'],
        ['info', 'solid-info-circle'],
        ['passed', 'solid-check-circle'],
        ['not-observed', 'solid-minus-circle'],
    ] as const)('should show the %s icon and badge', async (status, icon) => {
        const wrapper = await createWrapper({ status });

        expect(wrapper.classes()).toContain(`swag-paypal-settings-diagnostics-item--${status}`);
        expect(wrapper.vm.icon).toBe(icon);
        expect(wrapper.find(`${BLOCK}__icon`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__badge`).text()).toBe(`swag-paypal-settings.diagnostics.status.${status}`);
    });

    it('should show the subtitle only when given', async () => {
        expect((await createWrapper()).find(`${BLOCK}__subtitle`).exists()).toBe(false);

        const wrapper = await createWrapper({ subtitle: 'Express Checkout button' });

        expect(wrapper.find(`${BLOCK}__subtitle`).text()).toBe('Express Checkout button');
    });

    it('should replace the subtitle prop with the subtitle slot', async () => {
        const wrapper = await createWrapper({ subtitle: 'Prop' }, { subtitle: '<a href="#">Slot</a>' });

        expect(wrapper.find(`${BLOCK}__subtitle`).text()).toBe('Slot');
        expect(wrapper.find(`${BLOCK}__subtitle a`).exists()).toBe(true);
        expect(wrapper.element.tagName).toBe('DIV');
    });

    it('should show the subtitle slot without the subtitle prop', async () => {
        const wrapper = await createWrapper({}, { subtitle: 'Slot' });

        expect(wrapper.find(`${BLOCK}__subtitle`).text()).toBe('Slot');
    });

    it('should leave out the badge in the compact form', async () => {
        const wrapper = await createWrapper({ compact: true }, { default: '<p>Details</p>' });

        expect(wrapper.classes()).toContain('swag-paypal-settings-diagnostics-item--compact');
        expect(wrapper.find(`${BLOCK}__badge`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__icon`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__chevron`).exists()).toBe(true);
    });
});

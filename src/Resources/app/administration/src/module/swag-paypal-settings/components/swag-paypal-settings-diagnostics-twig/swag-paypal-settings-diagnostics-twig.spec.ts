import { mount } from '@vue/test-utils';
import type * as PayPal from 'SwagPayPal/types';
import SwagPayPalSettingsDiagnosticsTwig from '.';
import SwagPayPalSettingsDiagnosticsItem from '../swag-paypal-settings-diagnostics-item';
import SwagPayPalSettingsDiagnosticsSection from '../swag-paypal-settings-diagnostics-section';

Shopware.Component.register('swag-paypal-settings-diagnostics-item', Promise.resolve(SwagPayPalSettingsDiagnosticsItem));
Shopware.Component.register('swag-paypal-settings-diagnostics-section', Promise.resolve(SwagPayPalSettingsDiagnosticsSection));
Shopware.Component.register('swag-paypal-settings-diagnostics-twig', Promise.resolve(SwagPayPalSettingsDiagnosticsTwig));

type TwigResult = PayPal.Diagnostics<'twig_result'>;
type TemplateOverride = PayPal.Diagnostics<'twig_template_override'>;

const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';
const OTHER_SALES_CHANNEL_ID = '0188da1fd6ee4a7f8e7d8a6eb2cbd2c1';

function createOverride(override: Partial<TemplateOverride> = {}): TemplateOverride {
    return {
        type: 'missing_parent',
        severity: 'error',
        templatePath: 'storefront/page/checkout/cart/index.html.twig',
        namespace: 'SwagExampleTheme',
        file: 'custom/plugins/SwagExampleTheme/src/Resources/views/storefront/page/checkout/cart/index.html.twig',
        line: 12,
        block: 'page_checkout_aside_actions',
        affected: [{ block: 'page_checkout_aside_actions_paypal_express_button', feature: 'express_checkout_button' }],
        ...override,
    };
}

function createResult(storefront: TemplateOverride[] = [], documents: TemplateOverride[] = []): TwigResult {
    return {
        storefront: { applicable: true, overrides: storefront },
        documents: { applicable: true, overrides: documents },
    };
}

async function createWrapper(
    twig: () => Promise<TwigResult> = () => Promise.resolve(createResult()),
    mocks: Record<string, unknown> = {},
) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-twig') as typeof SwagPayPalSettingsDiagnosticsTwig,
        {
            global: {
                stubs: {
                    'swag-paypal-settings-diagnostics-item': await Shopware.Component.build('swag-paypal-settings-diagnostics-item'),
                    'swag-paypal-settings-diagnostics-section': await Shopware.Component.build('swag-paypal-settings-diagnostics-section'),
                },
                mocks,
                provide: {
                    SwagPayPalDiagnosticsService: { twig: jest.fn(twig) },
                },
            },
        },
    );
}

describe('swag-paypal-settings-diagnostics-twig', () => {
    it('should ask for a Sales Channel without checking', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.twig).not.toHaveBeenCalled();
        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__select-sales-channel').exists()).toBe(true);
        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__check-button').attributes('disabled')).toBeDefined();
    });

    it('should check the selected Sales Channel', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.twig).toHaveBeenCalledWith(SALES_CHANNEL_ID);
        expect(wrapper.findAll('.swag-paypal-settings-diagnostics-twig__area').map((area) => area.classes()))
            .toEqual([
                expect.arrayContaining(['swag-paypal-settings-diagnostics-twig__area--storefront']),
                expect.arrayContaining(['swag-paypal-settings-diagnostics-twig__area--documents']),
            ]);
        expect(wrapper.findAll('.swag-paypal-settings-diagnostics-twig__no-overrides')).toHaveLength(2);
    });

    it('should check each Sales Channel once', async () => {
        const store = Shopware.Store.get('swagPayPalSettings');
        store.salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        store.salesChannel = OTHER_SALES_CHANNEL_ID;
        await flushPromises();
        store.salesChannel = SALES_CHANNEL_ID;
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.twig).toHaveBeenCalledTimes(2);
    });

    it('should check again on click', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        await wrapper.find('.swag-paypal-settings-diagnostics-twig__check-button').trigger('click');
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.twig).toHaveBeenCalledTimes(2);
    });

    it('should show the overrides in the order of the result', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper(() => Promise.resolve(createResult([
            createOverride(),
            createOverride({ type: 'conditional_parent', severity: 'warning' }),
            createOverride({ type: 'unreadable', severity: 'notice', block: null }),
            createOverride({ type: 'dynamic_extends', severity: 'unknown', block: null }),
        ])));
        await flushPromises();

        const overrides = wrapper.findAll('.swag-paypal-settings-diagnostics-twig__area--storefront .swag-paypal-settings-diagnostics-twig__override');
        expect(overrides.map((override) => override.classes().find((cls) => cls.startsWith('swag-paypal-settings-diagnostics-item--'))))
            .toEqual([
                'swag-paypal-settings-diagnostics-item--error',
                'swag-paypal-settings-diagnostics-item--warning',
                'swag-paypal-settings-diagnostics-item--info',
                'swag-paypal-settings-diagnostics-item--not-observed',
            ]);
        expect(overrides[0].text()).toContain('swag-paypal-settings.diagnostics.twig.types.missing_parent.title');

        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__area--documents .swag-paypal-settings-diagnostics-twig__no-overrides').exists()).toBe(true);
    });

    it('should name the overridden block and the PayPal blocks around it', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper(() => Promise.resolve(createResult([
            createOverride(),
            createOverride({ type: 'template_replaced', block: null }),
        ])));
        await flushPromises();

        const [blockOverride, templateOverride] = wrapper.findAll('.swag-paypal-settings-diagnostics-twig__override');

        expect(blockOverride.find('.swag-paypal-settings-diagnostics-twig__overridden-block').text())
            .toBe('page_checkout_aside_actions');
        expect(blockOverride.text()).toContain('swag-paypal-settings.diagnostics.twig.labelContainedBlocks');
        expect(blockOverride.find('.swag-paypal-settings-diagnostics-twig__paypal-blocks').text())
            .toBe('page_checkout_aside_actions_paypal_express_button');

        expect(templateOverride.find('.swag-paypal-settings-diagnostics-twig__overridden-block').exists()).toBe(false);
        expect(templateOverride.text()).toContain('swag-paypal-settings.diagnostics.twig.labelPayPalBlocks');
        expect(templateOverride.find('.swag-paypal-settings-diagnostics-twig__paypal-blocks').text())
            .toBe('page_checkout_aside_actions_paypal_express_button');
    });

    it('should not repeat an overridden PayPal block as contained', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.paypalBlocks(createOverride({
            block: 'page_checkout_confirm_form_submit',
            affected: [
                { block: 'page_checkout_confirm_form_pui_disclaimer', feature: 'pui_disclaimer' },
                { block: 'page_checkout_confirm_form_submit_paypal_container', feature: 'checkout_buttons' },
            ],
        }))).toEqual(['page_checkout_confirm_form_pui_disclaimer', 'page_checkout_confirm_form_submit_paypal_container']);

        expect(wrapper.vm.paypalBlocks(createOverride({
            block: 'component_product_box_action_form_paypal_express',
            affected: [{ block: 'component_product_box_action_form_paypal_express', feature: 'express_checkout_button' }],
        }))).toEqual([]);
    });

    it('should explain areas that are not checked', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const result = createResult();
        result.storefront.applicable = false;

        const wrapper = await createWrapper(() => Promise.resolve(result));
        await flushPromises();

        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__area--storefront .swag-paypal-settings-diagnostics-twig__not-applicable').classes())
            .toContain('swag-paypal-settings-diagnostics-item--not-observed');
        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__area--storefront .swag-paypal-settings-diagnostics-twig__no-overrides').exists()).toBe(false);
    });

    it('should report a failed check', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');

        const wrapper = await createWrapper(() => Promise.reject({ response: { data: { errors: [{ code: 'SYSTEM__INTERNAL', detail: 'Something broke' }] } } }));
        await flushPromises();

        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__failed').exists()).toBe(true);
        expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
        expect(wrapper.find('.swag-paypal-settings-diagnostics-twig__check-button').attributes('disabled')).toBeUndefined();
    });

    it('should fold the details of an override into one line', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper(() => Promise.resolve(createResult([createOverride()])));
        await flushPromises();

        const override = wrapper.find('.swag-paypal-settings-diagnostics-twig__override');
        expect(override.element.tagName).toBe('DETAILS');
        expect(override.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.twig.types.missing_parent.title');
        expect(override.find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe('swag-paypal-settings.diagnostics.twig.features.express_checkout_button');
        expect(override.text()).toContain('swag-paypal-settings.diagnostics.twig.labelTemplate');
        expect(override.text()).not.toContain('swag-paypal-settings.diagnostics.twig.labelExtension');
    });

    it('should show an area without overrides as a flat passed row', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        const row = wrapper.find('.swag-paypal-settings-diagnostics-twig__area--documents .swag-paypal-settings-diagnostics-twig__no-overrides');
        expect(row.classes()).toContain('swag-paypal-settings-diagnostics-item--passed');
        expect(row.element.tagName).toBe('DIV');
        expect(row.find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe('swag-paypal-settings.diagnostics.twig.noOverridesSubtitle');
        expect(wrapper.findAll('.swag-paypal-settings-diagnostics-section__title').map((title) => title.text())).toEqual([
            'swag-paypal-settings.diagnostics.twig.areas.storefront.title',
            'swag-paypal-settings.diagnostics.twig.areas.documents.title',
        ]);
    });

    it('should locate templates with and without a file', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.location(createOverride()))
            .toBe('custom/plugins/SwagExampleTheme/src/Resources/views/storefront/page/checkout/cart/index.html.twig:12');
        expect(wrapper.vm.location(createOverride({ file: null })))
            .toBe('storefront/page/checkout/cart/index.html.twig:12');
    });

    it('should name each affected feature once', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.featureLabels(createOverride({
            affected: [
                { block: 'page_checkout_aside_actions_paypal_express_button', feature: 'express_checkout_button' },
                { block: 'page_checkout_aside_actions_paypal_installment_banner', feature: 'pay_later_banner' },
                { block: 'page_checkout_cart_table_items_paypal_installment_banner', feature: 'pay_later_banner' },
            ],
        }))).toBe([
            'swag-paypal-settings.diagnostics.twig.features.express_checkout_button',
            'swag-paypal-settings.diagnostics.twig.features.pay_later_banner',
        ].join(', '));
    });

    it('should name features without a snippet by their id', async () => {
        const wrapper = await createWrapper(undefined, { $te: () => false });

        expect(wrapper.vm.featureLabels(createOverride({
            affected: [{ block: 'some_block', feature: 'some_new_feature' }],
        }))).toBe('some_new_feature');
    });
});

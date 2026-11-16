import { config, mount } from '@vue/test-utils';
import type * as PayPal from 'SwagPayPal/types';
import SwagPayPalSettingsDiagnosticsExpressCallback from '.';
import SwagPayPalSettingsDiagnosticsItem from '../swag-paypal-settings-diagnostics-item';
import SwagPayPalSettingsDiagnosticsSection from '../swag-paypal-settings-diagnostics-section';
import SwagPayPalSettingsDiagnosticsReachability from '../swag-paypal-settings-diagnostics-reachability';

Shopware.Component.register('swag-paypal-settings-diagnostics-item', Promise.resolve(SwagPayPalSettingsDiagnosticsItem));
Shopware.Component.register('swag-paypal-settings-diagnostics-section', Promise.resolve(SwagPayPalSettingsDiagnosticsSection));
Shopware.Component.register('swag-paypal-settings-diagnostics-reachability', Promise.resolve(SwagPayPalSettingsDiagnosticsReachability));
Shopware.Component.register(
    'swag-paypal-settings-diagnostics-express-callback',
    Promise.resolve(SwagPayPalSettingsDiagnosticsExpressCallback),
);

type ExpressCallbackResult = PayPal.Diagnostics<'express_callback_result'>;
type ReachabilityResult = PayPal.Diagnostics<'reachability'>;

const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';
const OTHER_SALES_CHANNEL_ID = '0188da1fd6ee4a7f8e7d8a6eb2cbd2c1';

const BLOCK = '.swag-paypal-settings-diagnostics-express-callback';

const REACHABILITY = '.swag-paypal-settings-diagnostics-reachability';

function createResult(result: Partial<ExpressCallbackResult> = {}): ExpressCallbackResult {
    return {
        status: 'active',
        lastCallback: null,
        findings: [],
        ...result,
    };
}

async function createWrapper(service: {
    expressCallback?: () => Promise<ExpressCallbackResult>;
    expressCallbackReachability?: () => Promise<ReachabilityResult[]>;
    translate?: jest.Mock;
} = {}) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-express-callback') as typeof SwagPayPalSettingsDiagnosticsExpressCallback,
        {
            global: {
                ...(service.translate ? { mocks: { $t: service.translate } } : {}),
                stubs: {
                    'swag-paypal-settings-diagnostics-item': await Shopware.Component.build('swag-paypal-settings-diagnostics-item'),
                    'swag-paypal-settings-diagnostics-section': await Shopware.Component.build('swag-paypal-settings-diagnostics-section'),
                    'swag-paypal-settings-diagnostics-reachability': await Shopware.Component.build('swag-paypal-settings-diagnostics-reachability'),
                },
                provide: {
                    SwagPayPalDiagnosticsService: {
                        expressCallback: jest.fn(service.expressCallback ?? (() => Promise.resolve(createResult()))),
                        expressCallbackReachability: jest.fn(service.expressCallbackReachability ?? (() => Promise.resolve([]))),
                    },
                },
            },
        },
    );
}

function itemVariant(classes: string[]): string | undefined {
    return classes.find((cls) => cls.startsWith('swag-paypal-settings-diagnostics-item--'));
}

describe('swag-paypal-settings-diagnostics-express-callback', () => {
    it('should ask for a Sales Channel without checking', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallback).not.toHaveBeenCalled();
        expect(wrapper.find(`${BLOCK}__select-sales-channel`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeDefined();
    });

    it('should check each Sales Channel once and again on click', async () => {
        const store = Shopware.Store.get('swagPayPalSettings');
        store.salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        store.salesChannel = OTHER_SALES_CHANNEL_ID;
        await flushPromises();
        store.salesChannel = SALES_CHANNEL_ID;
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallback).toHaveBeenCalledTimes(2);

        await wrapper.find(`${BLOCK}__check-button`).trigger('click');
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallback).toHaveBeenCalledTimes(3);
        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallback).toHaveBeenLastCalledWith(SALES_CHANNEL_ID);
        // every time the reachability section shows up, it tests
        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).toHaveBeenCalledTimes(4);
        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).toHaveBeenLastCalledWith(SALES_CHANNEL_ID);
    });

    it.each([
        ['local_environment', 'swag-paypal-settings-diagnostics-item--warning'],
        ['disabled', 'swag-paypal-settings-diagnostics-item--info'],
        ['inactive', 'swag-paypal-settings-diagnostics-item--not-observed'],
    ] as const)('should explain the %s status', async (status, variant) => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ expressCallback: () => Promise.resolve(createResult({ status })) });
        await flushPromises();

        const banner = wrapper.find(`${BLOCK}__status`);
        expect(itemVariant(banner.classes())).toBe(variant);
        expect(banner.text()).toContain(`swag-paypal-settings.diagnostics.expressCallback.status.${status}.title`);
        expect(banner.text()).toContain(`swag-paypal-settings.diagnostics.expressCallback.status.${status}.summary`);
        expect(banner.element.tagName).toBe('DIV');
        expect(banner.find('.swag-paypal-settings-diagnostics-item__chevron').exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__no-callback`).exists()).toBe(false);
    });

    describe('links to the setting', () => {
        // i18n-t renders the link only for a message with its placeholder
        beforeAll(() => {
            const i18n = config.global.plugins.find((plugin) => 'global' in plugin) as unknown as {
                global: { mergeLocaleMessage: (locale: string, messages: Record<string, string>) => void };
            };
            i18n.global.mergeLocaleMessage('en', {
                'swag-paypal-settings.diagnostics.expressCallback.status.local_environment.summary': 'Turn it off in the {advancedTab}.',
                'swag-paypal-settings.diagnostics.expressCallback.status.disabled.summary': 'Turn it on in the {storefrontTab}.',
            });
        });

        it.each([
            ['local_environment', 'advanced', 'swag-paypal-settings.diagnostics.advancedTab'],
            ['disabled', 'storefront', 'swag-paypal-settings.diagnostics.storefrontTab'],
        ] as const)('should link the %s status to the %s tab', async (status, tab, text) => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ expressCallback: () => Promise.resolve(createResult({ status })) });
            await flushPromises();

            const link = wrapper.findComponent<{ props: () => { to: unknown } }>(`${BLOCK}__status .mt-link`);
            expect(link.props('to')).toEqual({ name: `swag.paypal.settings.index.${tab}` });
            expect(link.text()).toBe(text);
        });
    });

    it('should not test the reachability when Express Checkout is inactive', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ expressCallback: () => Promise.resolve(createResult({ status: 'inactive' })) });
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).not.toHaveBeenCalled();
        expect(wrapper.find(`${BLOCK}__reachability`).exists()).toBe(false);
    });

    it('should test the reachability while the callback is turned off', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ expressCallback: () => Promise.resolve(createResult({ status: 'local_environment' })) });
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).toHaveBeenCalledWith(SALES_CHANNEL_ID);
    });

    it('should show the reachability test running', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ expressCallbackReachability: () => new Promise(() => {}) });
        await flushPromises();

        expect(wrapper.find(`${REACHABILITY}__testing`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeDefined();
    });

    it('should show the findings and the last callback of an active callback', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallback: () => Promise.resolve(createResult({
                lastCallback: { at: '2026-11-12T10:00:00+00:00', outcome: 'answered' },
                findings: [
                    { type: 'untrusted_proxy', severity: 'warning', host: null },
                    { type: 'sandbox', severity: 'notice', host: null },
                ],
            })),
        });
        await flushPromises();

        expect(wrapper.find(`${BLOCK}__status`).exists()).toBe(false);
        expect(wrapper.findAll(`${BLOCK}__finding`).map((finding) => itemVariant(finding.classes())))
            .toEqual(['swag-paypal-settings-diagnostics-item--warning', 'swag-paypal-settings-diagnostics-item--info']);
        expect(itemVariant(wrapper.find(`${BLOCK}__last-callback`).classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
        expect(wrapper.vm.lastCallbackDate).not.toBe('');
        expect(wrapper.find(`${BLOCK}__last-callback`).element.tagName).toBe('DIV');
        expect(wrapper.find(`${BLOCK}__last-callback .swag-paypal-settings-diagnostics-item__chevron`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__last-callback .swag-paypal-settings-diagnostics-item__subtitle`).text())
            .toBe('swag-paypal-settings.diagnostics.expressCallback.lastCallback.outcome.answered');
        expect(wrapper.find(`${BLOCK}__no-callback`).exists()).toBe(false);
    });

    it('should show the severities as they come', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallback: () => Promise.resolve(createResult({
                findings: [
                    { type: 'last_callback_failed', severity: 'error', host: null },
                    { type: 'untrusted_proxy', severity: 'warning', host: null },
                    { type: 'sandbox', severity: 'notice', host: null },
                    { type: 'store_api_url_unknown', severity: 'unknown', host: null },
                ],
            })),
        });
        await flushPromises();

        const findings = wrapper.findAll(`${BLOCK}__finding`);
        expect(findings.map((finding) => itemVariant(finding.classes()))).toEqual([
            'swag-paypal-settings-diagnostics-item--error',
            'swag-paypal-settings-diagnostics-item--warning',
            'swag-paypal-settings-diagnostics-item--info',
            'swag-paypal-settings-diagnostics-item--not-observed',
        ]);
    });

    it('should name the host of a Store API URL that isn\'t the shop\'s', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const translate = jest.fn((key: string) => key);
        const wrapper = await createWrapper({
            translate,
            expressCallback: () => Promise.resolve(createResult({
                findings: [{ type: 'store_api_host_unknown', severity: 'unknown', host: 'evil.example' }],
            })),
        });
        await flushPromises();

        expect(itemVariant(wrapper.find(`${BLOCK}__finding`).classes())).toBe('swag-paypal-settings-diagnostics-item--not-observed');
        expect(wrapper.find(`${BLOCK}__finding`).element.tagName).toBe('DIV');
        expect(translate).toHaveBeenCalledWith(
            'swag-paypal-settings.diagnostics.expressCallback.findings.store_api_host_unknown.summary',
            { date: '', host: 'evil.example' },
        );
    });

    it('should split the card into its sections', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallbackReachability: () => Promise.resolve([{
                url: 'https://shop.example/paypal/express/shipping-callback',
                error: null,
                severity: null,
                addresses: ['93.184.216.34'],
                statusCode: 400,
                location: null,
                detail: null,
            }]),
        });
        await flushPromises();

        expect(wrapper.findAll('.swag-paypal-settings-diagnostics-section__title').map((title) => title.text())).toEqual([
            'swag-paypal-settings.diagnostics.sectionActivity',
            'swag-paypal-settings.diagnostics.reachability.title',
        ]);
    });

    it('should explain how to get a first callback', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        const noCallback = wrapper.find(`${BLOCK}__no-callback`);
        expect(itemVariant(noCallback.classes())).toBe('swag-paypal-settings-diagnostics-item--not-observed');
        expect(noCallback.element.tagName).toBe('DETAILS');
        expect(noCallback.find('.swag-paypal-settings-diagnostics-item__content').text())
            .toBe('swag-paypal-settings.diagnostics.expressCallback.noCallback.description');
        expect(wrapper.find(`${BLOCK}__last-callback`).exists()).toBe(false);
    });

    it('should fold open only the explanation of an untrusted proxy', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallback: () => Promise.resolve(createResult({
                lastCallback: { at: '2026-11-12T10:00:00+00:00', outcome: 'answered' },
                findings: [
                    { type: 'untrusted_proxy', severity: 'warning', host: null },
                    { type: 'sandbox', severity: 'notice', host: null },
                ],
            })),
        });
        await flushPromises();

        const [proxy, sandbox] = wrapper.findAll(`${BLOCK}__finding`);
        expect(proxy.element.tagName).toBe('DETAILS');
        expect(proxy.find('.swag-paypal-settings-diagnostics-item__content').text())
            .toBe('swag-paypal-settings.diagnostics.expressCallback.findings.untrusted_proxy.description');
        expect(sandbox.element.tagName).toBe('DIV');
        expect(sandbox.find('.swag-paypal-settings-diagnostics-item__content').exists()).toBe(false);
    });

    it('should leave a failed last callback to its finding', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallback: () => Promise.resolve(createResult({
                lastCallback: { at: '2026-11-12T10:00:00+00:00', outcome: 'failed' },
                findings: [{ type: 'last_callback_failed', severity: 'error', host: null }],
            })),
        });
        await flushPromises();

        expect(itemVariant(wrapper.find(`${BLOCK}__finding`).classes())).toBe('swag-paypal-settings-diagnostics-item--error');
        expect(wrapper.find(`${BLOCK}__last-callback`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__no-callback`).exists()).toBe(false);
    });

    it('should test the reachability right after the check', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            expressCallbackReachability: () => Promise.resolve([
                {
                    url: `https://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`,
                    error: null,
                    severity: null,
                    addresses: ['93.184.216.34'],
                    statusCode: 400,
                    location: null,
                    detail: null,
                },
                {
                    url: `http://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`,
                    error: 'redirected',
                    severity: 'error',
                    addresses: ['93.184.216.34'],
                    statusCode: 301,
                    location: 'https://shop.example/maintenance',
                    detail: null,
                },
            ]),
        });
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).toHaveBeenCalledWith(SALES_CHANNEL_ID);

        const results = wrapper.findAll(`${REACHABILITY}__result`);
        expect(results.map((result) => itemVariant(result.classes()))).toEqual([
            'swag-paypal-settings-diagnostics-item--passed',
            'swag-paypal-settings-diagnostics-item--error',
        ]);
        expect(results[0].find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe(`https://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`);
        expect(results[1].find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe(`http://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`);
    });

    it('should render no reachability section when there is no URL to test', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).toHaveBeenCalledWith(SALES_CHANNEL_ID);
        expect(wrapper.find(REACHABILITY).exists()).toBe(false);
    });

    it('should report failed requests', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');
        const error = { response: { data: { errors: [{ code: 'SYSTEM__INTERNAL', detail: 'Something broke' }] } } };

        const wrapper = await createWrapper({
            expressCallback: () => Promise.reject(error),
        });
        await flushPromises();

        expect(wrapper.find(`${BLOCK}__failed`).exists()).toBe(true);
        expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
        expect(wrapper.vm.SwagPayPalDiagnosticsService.expressCallbackReachability).not.toHaveBeenCalled();
    });

    it('should report a failed reachability test', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');

        const wrapper = await createWrapper({
            expressCallbackReachability: () => Promise.reject({ response: { data: { errors: [] } } }),
        });
        await flushPromises();

        expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
        expect(wrapper.find(`${REACHABILITY}__failed`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeUndefined();
    });
});

import { config, mount } from '@vue/test-utils';
import type * as PayPal from 'SwagPayPal/types';
import SwagPayPalSettingsDiagnosticsItem from '../swag-paypal-settings-diagnostics-item';
import SwagPayPalSettingsDiagnosticsSection from '../swag-paypal-settings-diagnostics-section';
import SwagPayPalSettingsDiagnosticsReachability from '../swag-paypal-settings-diagnostics-reachability';
import SwagPayPalSettingsDiagnosticsWebhook from '.';

Shopware.Component.register('swag-paypal-settings-diagnostics-item', Promise.resolve(SwagPayPalSettingsDiagnosticsItem));
Shopware.Component.register('swag-paypal-settings-diagnostics-section', Promise.resolve(SwagPayPalSettingsDiagnosticsSection));
Shopware.Component.register('swag-paypal-settings-diagnostics-reachability', Promise.resolve(SwagPayPalSettingsDiagnosticsReachability));
Shopware.Component.register('swag-paypal-settings-diagnostics-webhook', Promise.resolve(SwagPayPalSettingsDiagnosticsWebhook));

type WebhookResult = PayPal.Diagnostics<'webhook_result'>;
type ReachabilityResult = PayPal.Diagnostics<'reachability'>;
type LastWebhook = PayPal.Diagnostics<'webhook_last_webhook'>;

const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';
const OTHER_SALES_CHANNEL_ID = '0188da1fd6ee4a7f8e7d8a6eb2cbd2c1';

const BLOCK = '.swag-paypal-settings-diagnostics-webhook';
const REACHABILITY = '.swag-paypal-settings-diagnostics-reachability';

const TEST_EVENT_ID = 'WH-TEST';

const TEST_SENT_AT = '2026-11-12T09:59:58+00:00';

function createReachabilityResult(): ReachabilityResult {
    return {
        url: 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>',
        error: null,
        severity: null,
        addresses: ['93.184.216.34'],
        statusCode: 400,
        location: null,
        detail: null,
    };
}

function createResult(result: Partial<WebhookResult> = {}): WebhookResult {
    return {
        registration: 'valid',
        lastWebhook: null,
        registeredUrl: null,
        candidateUrls: [],
        missedEvents: null,
        findings: [],
        ...result,
    };
}

async function createWrapper(service: {
    webhook?: () => Promise<WebhookResult>;
    webhookReachability?: () => Promise<ReachabilityResult[]>;
    webhookTest?: (salesChannelId: string, target: 'registered' | 'administration' | 'app_url') => Promise<{ eventId: string; sentAt: string }>;
    webhookTestArrival?: () => Promise<{ arrived: boolean }>;
    translate?: jest.Mock;
} = {}) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-webhook') as typeof SwagPayPalSettingsDiagnosticsWebhook,
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
                        webhook: jest.fn(service.webhook ?? (() => Promise.resolve(createResult()))),
                        webhookReachability: jest.fn(service.webhookReachability ?? (() => Promise.resolve([]))),
                        webhookTest: jest.fn(service.webhookTest ?? (() => Promise.resolve({ eventId: TEST_EVENT_ID, sentAt: TEST_SENT_AT }))),
                        webhookTestArrival: jest.fn(service.webhookTestArrival ?? (() => Promise.resolve({ arrived: false }))),
                    },
                },
            },
        },
    );
}

function createLastWebhook(eventId: string, at = '2026-11-12T10:00:00+00:00'): LastWebhook {
    return {
        at,
        eventType: 'CHECKOUT.ORDER.APPROVED',
        outcome: 'handled',
        eventId,
    };
}

function itemVariant(classes: string[]): string | undefined {
    return classes.find((cls) => cls.startsWith('swag-paypal-settings-diagnostics-item--'));
}

describe('swag-paypal-settings-diagnostics-webhook', () => {
    it('should ask for a Sales Channel without checking', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).not.toHaveBeenCalled();
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

        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).toHaveBeenCalledTimes(2);

        await wrapper.find(`${BLOCK}__check-button`).trigger('click');
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).toHaveBeenCalledTimes(3);
        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).toHaveBeenLastCalledWith(SALES_CHANNEL_ID);
        // every time the reachability section shows up, it tests
        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookReachability).toHaveBeenCalledTimes(4);
        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookReachability).toHaveBeenLastCalledWith(SALES_CHANNEL_ID);
    });

    it('should explain the local development environment without testing the reachability', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ webhook: () => Promise.resolve(createResult({ registration: 'disabled' })) });
        await flushPromises();

        const item = wrapper.find(`${BLOCK}__disabled`);
        expect(itemVariant(item.classes())).toBe('swag-paypal-settings-diagnostics-item--warning');
        expect(item.text()).toContain('swag-paypal-settings.diagnostics.webhook.disabled.title');
        expect(item.text()).toContain('swag-paypal-settings.diagnostics.webhook.disabled.summary');
        expect(item.element.tagName).toBe('DIV');
        expect(wrapper.find(`${BLOCK}__registered`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__no-webhook`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__reachability`).exists()).toBe(false);
        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookReachability).not.toHaveBeenCalled();
    });

    it('should show the registration and the last webhook of a working shop', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                lastWebhook: { at: '2026-11-12T10:00:00+00:00', eventType: 'PAYMENT.CAPTURE.COMPLETED', outcome: 'handled', eventId: 'WH-1' },
            })),
        });
        await flushPromises();

        const registered = wrapper.find(`${BLOCK}__registered`);
        expect(itemVariant(registered.classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
        expect(registered.element.tagName).toBe('DIV');

        const lastWebhook = wrapper.find(`${BLOCK}__last-webhook`);
        expect(itemVariant(lastWebhook.classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
        expect(lastWebhook.element.tagName).toBe('DIV');
        expect(lastWebhook.find('.swag-paypal-settings-diagnostics-item__subtitle').text()).toBe('PAYMENT.CAPTURE.COMPLETED');
        expect(lastWebhook.find('.swag-paypal-settings-diagnostics-item__chevron').exists()).toBe(false);
        expect(wrapper.vm.lastWebhookDate).not.toBe('');
        expect(wrapper.find(`${BLOCK}__finding`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__no-webhook`).exists()).toBe(false);
    });

    it('should explain how to get a first webhook', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper();
        await flushPromises();

        expect(itemVariant(wrapper.find(`${BLOCK}__no-webhook`).classes())).toBe('swag-paypal-settings-diagnostics-item--not-observed');
        expect(wrapper.find(`${BLOCK}__last-webhook`).exists()).toBe(false);
    });

    it('should show the findings with their severity', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                registration: 'invalid',
                lastWebhook: { at: '2026-11-12T10:00:00+00:00', eventType: 'PAYMENT.CAPTURE.COMPLETED', outcome: 'failed', eventId: 'WH-1' },
                findings: [
                    { type: 'registration_invalid', severity: 'warning' },
                    { type: 'last_webhook_failed', severity: 'error' },
                ],
            })),
        });
        await flushPromises();

        expect(wrapper.findAll(`${BLOCK}__finding`).map((finding) => itemVariant(finding.classes()))).toEqual([
            'swag-paypal-settings-diagnostics-item--warning',
            'swag-paypal-settings-diagnostics-item--error',
        ]);
        expect(wrapper.find(`${BLOCK}__registered`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__last-webhook`).exists()).toBe(false);
        expect(wrapper.find(`${BLOCK}__no-webhook`).exists()).toBe(false);
    });

    it('should not count an unchecked registration as an error', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                registration: 'unknown',
                findings: [{ type: 'registration_unknown', severity: 'unknown' }],
            })),
        });
        await flushPromises();

        expect(itemVariant(wrapper.find(`${BLOCK}__finding`).classes())).toBe('swag-paypal-settings-diagnostics-item--not-observed');
        expect(wrapper.find(`${BLOCK}__finding .swag-paypal-settings-diagnostics-item__subtitle`).text())
            .toBe('swag-paypal-settings.diagnostics.webhook.findings.registration_unknown.summary');
    });

    it('should split the card into its sections', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhookReachability: () => Promise.resolve([createReachabilityResult()]),
        });
        await flushPromises();

        expect(wrapper.findAll('.swag-paypal-settings-diagnostics-section__title').map((title) => title.text())).toEqual([
            'swag-paypal-settings.diagnostics.sectionActivity',
            'swag-paypal-settings.diagnostics.reachability.title',
        ]);
    });

    it('should test the reachability right after the check', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhookReachability: () => Promise.resolve([createReachabilityResult()]),
        });
        await flushPromises();

        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookReachability).toHaveBeenCalledWith(SALES_CHANNEL_ID);

        const results = wrapper.findAll(`${REACHABILITY}__result`);
        expect(results).toHaveLength(1);
        expect(itemVariant(results[0].classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
        expect(results[0].find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe('https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>');
    });

    it('should show the reachability test running', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({ webhookReachability: () => new Promise(() => {}) });
        await flushPromises();

        expect(wrapper.find(`${REACHABILITY}__testing`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeDefined();
    });

    it('should report a failed check', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');
        const error = { response: { data: { errors: [{ code: 'SYSTEM__INTERNAL', detail: 'Something broke' }] } } };

        const wrapper = await createWrapper({ webhook: () => Promise.reject(error) });
        await flushPromises();

        expect(wrapper.find(`${BLOCK}__failed`).exists()).toBe(true);
        expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
        expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookReachability).not.toHaveBeenCalled();
    });

    it('should report a failed reachability test', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');

        const wrapper = await createWrapper({
            webhookReachability: () => Promise.reject({ response: { data: { errors: [] } } }),
        });
        await flushPromises();

        expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
        expect(wrapper.find(`${REACHABILITY}__failed`).exists()).toBe(true);
        expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeUndefined();
    });

    it('should fold open only the findings that offer something to do', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                registration: 'invalid',
                registeredUrl: 'https://old-shop.example/webhook',
                candidateUrls: [{ source: 'administration', url: 'https://shop.example/webhook' }],
                lastWebhook: { at: '2026-11-12T10:00:00+00:00', eventType: 'PAYMENT.CAPTURE.COMPLETED', outcome: 'failed', eventId: 'WH-1' },
                findings: [
                    { type: 'registration_invalid', severity: 'warning' },
                    { type: 'last_webhook_failed', severity: 'error' },
                ],
            })),
        });
        await flushPromises();

        const [invalid, failed] = wrapper.findAll(`${BLOCK}__finding`);
        expect(invalid.element.tagName).toBe('DETAILS');
        expect(invalid.find('.swag-paypal-settings-diagnostics-item__content').text())
            .toContain('swag-paypal-settings.diagnostics.webhook.findings.registration_invalid.description');
        expect(failed.element.tagName).toBe('DIV');
        expect(failed.find('.swag-paypal-settings-diagnostics-item__chevron').exists()).toBe(false);
    });

    it('should show the URLs of a webhook registered for another URL', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                registration: 'invalid',
                registeredUrl: 'https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=<token>',
                candidateUrls: [{ source: 'administration', url: 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>' }],
                findings: [{ type: 'registration_invalid', severity: 'warning' }],
            })),
        });
        await flushPromises();

        expect(wrapper.find(`${BLOCK}__registered-url code`).text())
            .toBe('https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=<token>');
        expect(wrapper.find(`${BLOCK}__candidate-url--administration code`).text())
            .toBe('https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>');
    });

    it('should show the URLs of a webhook registered with an outdated token', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        const url = 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>';

        const wrapper = await createWrapper({
            webhook: () => Promise.resolve(createResult({
                registration: 'invalid',
                registeredUrl: url,
                candidateUrls: [{ source: 'administration', url }],
                findings: [{ type: 'registration_outdated', severity: 'warning' }],
            })),
        });
        await flushPromises();

        const finding = wrapper.find(`${BLOCK}__finding`);
        expect(itemVariant(finding.classes())).toBe('swag-paypal-settings-diagnostics-item--warning');
        expect(finding.element.tagName).toBe('DETAILS');
        expect(finding.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.webhook.findings.registration_outdated.title');
        expect(finding.find('.swag-paypal-settings-diagnostics-item__content').text())
            .toContain('swag-paypal-settings.diagnostics.webhook.findings.registration_outdated.description');
        expect(wrapper.find(`${BLOCK}__registered-url code`).text()).toBe(url);
        expect(wrapper.find(`${BLOCK}__candidate-url--administration code`).text()).toBe(url);
    });

    it('should explain missed events', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const translate = jest.fn((key: string) => key);
        const wrapper = await createWrapper({
            translate,
            webhook: () => Promise.resolve(createResult({
                lastWebhook: createLastWebhook('WH-1'),
                missedEvents: { count: 20, more: true, newestAt: '2026-11-12T10:30:00.000Z', newestEventType: 'PAYMENT.CAPTURE.REFUNDED' },
                findings: [{ type: 'missed_events', severity: 'warning' }],
            })),
        });
        await flushPromises();

        const finding = wrapper.find(`${BLOCK}__finding`);
        expect(itemVariant(finding.classes())).toBe('swag-paypal-settings-diagnostics-item--warning');
        expect(finding.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.webhook.findings.missed_events.title');
        expect(finding.element.tagName).toBe('DIV');
        expect(finding.find('.swag-paypal-settings-diagnostics-item__content').exists()).toBe(false);

        expect(translate).toHaveBeenCalledWith(
            'swag-paypal-settings.diagnostics.webhook.findings.missed_events.summary',
            { date: wrapper.vm.formatDate('2026-11-12T10:30:00.000Z'), count: '20+', eventType: 'PAYMENT.CAPTURE.REFUNDED' },
            20,
        );
    });

    describe('missed events plural', () => {
        const i18n = config.global.plugins.find((plugin) => 'global' in plugin) as unknown as {
            global: {
                mergeLocaleMessage: (locale: string, messages: Record<string, string>) => void;
                t: (key: string, named: Record<string, unknown>, plural: number) => string;
            };
        };

        beforeAll(() => {
            i18n.global.mergeLocaleMessage('en', {
                'swag-paypal-settings.diagnostics.webhook.findings.missed_events.summary': '{count} event since | {count} events since',
            });
        });

        it.each([
            [1, false, '1 event since'],
            [2, false, '2 events since'],
            [20, true, '20+ events since'],
        ])('should word %i missed events (more: %s)', async (count, more, text) => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({
                translate: jest.fn((key: string, named?: Record<string, unknown>, plural?: number) => (
                    key.endsWith('missed_events.summary') ? i18n.global.t(key, named ?? {}, plural ?? 1) : key
                )),
                webhook: () => Promise.resolve(createResult({
                    lastWebhook: createLastWebhook('WH-1'),
                    missedEvents: { count, more, newestAt: '2026-11-12T10:30:00.000Z', newestEventType: 'PAYMENT.CAPTURE.REFUNDED' },
                    findings: [{ type: 'missed_events', severity: 'warning' }],
                })),
            });
            await flushPromises();

            expect(wrapper.find(`${BLOCK}__finding .swag-paypal-settings-diagnostics-item__subtitle`).text()).toBe(text);
        });
    });

    it('should offer the test webhook only for a valid registration', async () => {
        Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

        const valid = await createWrapper();
        await flushPromises();
        expect(valid.find(`${BLOCK}__test-button`).exists()).toBe(true);

        for (const registration of ['invalid', 'missing', 'disabled', 'unknown'] as const) {
            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createResult({ registration })) });
            await flushPromises();

            expect(wrapper.find(`${BLOCK}__test-button`).exists()).toBe(false);
        }
    });

    describe('test webhook', () => {
        beforeEach(() => {
            jest.useFakeTimers();
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
        });

        afterEach(() => {
            jest.useRealTimers();
        });

        it('should wait for the test webhook until it arrives and check again', async () => {
            const testArrival = jest.fn()
                .mockResolvedValueOnce({ arrived: false })
                .mockResolvedValueOnce({ arrived: true });

            const wrapper = await createWrapper({ webhookTestArrival: testArrival });
            await flushPromises();
            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).toHaveBeenCalledTimes(1);

            await wrapper.find(`${BLOCK}__test-button`).trigger('click');
            await flushPromises();

            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTest).toHaveBeenCalledWith(SALES_CHANNEL_ID, 'registered');
            const waiting = wrapper.find(`${BLOCK}__test`);
            expect(itemVariant(waiting.classes())).toBe('swag-paypal-settings-diagnostics-item--not-observed');
            expect(waiting.text()).toContain('swag-paypal-settings.diagnostics.webhook.test.waiting');
            expect(waiting.find('.swag-paypal-settings-diagnostics-item__subtitle').text()).toBe('swag-paypal-settings.diagnostics.webhook.labelRegisteredUrl');
            expect(wrapper.find(`${BLOCK}__test-button`).attributes('disabled')).toBeDefined();
            expect(wrapper.find(`${BLOCK}__check-button`).attributes('disabled')).toBeDefined();

            jest.advanceTimersByTime(3000);
            await flushPromises();
            expect(testArrival).toHaveBeenCalledTimes(1);
            expect(wrapper.find(`${BLOCK}__test`).classes()).toContain(`${BLOCK.slice(1)}__test--running`);

            jest.advanceTimersByTime(3000);
            await flushPromises();

            expect(testArrival).toHaveBeenCalledTimes(2);
            expect(testArrival).toHaveBeenCalledWith(SALES_CHANNEL_ID);
            const arrived = wrapper.find(`${BLOCK}__test`);
            expect(itemVariant(arrived.classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
            expect(arrived.classes()).toContain(`${BLOCK.slice(1)}__test--arrived`);
            expect(arrived.find('.swag-paypal-settings-diagnostics-item__subtitle').text())
                .toBe('swag-paypal-settings.diagnostics.webhook.test.arrived.subtitle');
            expect(wrapper.vm.webhookTests[SALES_CHANNEL_ID]).toEqual({ status: 'arrived', target: 'registered', seconds: 6 });
            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhook).toHaveBeenCalledTimes(2);

            jest.advanceTimersByTime(60000);
            await flushPromises();
            expect(testArrival).toHaveBeenCalledTimes(2);
        });

        it('should give up after a minute', async () => {
            const testArrival = jest.fn().mockResolvedValue({ arrived: false });

            const wrapper = await createWrapper({ webhookTestArrival: testArrival });
            await flushPromises();

            await wrapper.find(`${BLOCK}__test-button`).trigger('click');
            await flushPromises();

            for (let i = 0; i < 20; i += 1) {
                jest.advanceTimersByTime(3000);
                await flushPromises();
            }

            expect(testArrival).toHaveBeenCalledTimes(20);
            const missing = wrapper.find(`${BLOCK}__test`);
            expect(itemVariant(missing.classes())).toBe('swag-paypal-settings-diagnostics-item--error');
            expect(missing.classes()).toContain(`${BLOCK.slice(1)}__test--missing`);
            expect(missing.element.tagName).toBe('DETAILS');
            expect(missing.text()).toContain('swag-paypal-settings.diagnostics.webhook.test.missing.description');
            expect(missing.text()).not.toContain('swag-paypal-settings.diagnostics.webhook.test.missing.descriptionCandidate');
            expect(wrapper.find(`${BLOCK}__test-button`).attributes('disabled')).toBeUndefined();

            jest.advanceTimersByTime(30000);
            await flushPromises();
            expect(testArrival).toHaveBeenCalledTimes(20);
        });

        it('should report a test webhook PayPal couldn\'t send', async () => {
            const createNotification = jest.spyOn(Shopware.Store.get('notification'), 'createNotification');
            const webhookTest = jest.fn().mockRejectedValue({ response: { data: { errors: [{ code: 'SWAG_PAYPAL__API', detail: 'Not allowed' }] } } });

            const wrapper = await createWrapper({ webhookTest });
            await flushPromises();

            await wrapper.find(`${BLOCK}__test-button`).trigger('click');
            await flushPromises();

            const failed = wrapper.find(`${BLOCK}__test`);
            expect(itemVariant(failed.classes())).toBe('swag-paypal-settings-diagnostics-item--error');
            expect(failed.classes()).toContain(`${BLOCK.slice(1)}__test--failed`);
            expect(failed.element.tagName).toBe('DIV');
            expect(createNotification).toHaveBeenCalledWith(expect.objectContaining({ variant: 'error' }));
            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTestArrival).not.toHaveBeenCalled();
            expect(wrapper.find(`${BLOCK}__test-button`).attributes('disabled')).toBeUndefined();
        });

        describe('with a registration for another URL', () => {
            const invalid = () => Promise.resolve(createResult({
                registration: 'invalid',
                registeredUrl: 'https://old-shop.example/api/_action/paypal/webhook/execute?sw-token=<token>',
                candidateUrls: [{ source: 'administration', url: 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>' }],
                findings: [{ type: 'registration_invalid', severity: 'warning' }],
            }));

            it('should offer a test webhook for each URL', async () => {
                const wrapper = await createWrapper({ webhook: invalid });
                await flushPromises();

                expect(wrapper.find(`${BLOCK}__test-button`).exists()).toBe(false);
                expect(wrapper.find(`${BLOCK}__registered-url ${BLOCK}__test-registered-button`).exists()).toBe(true);
                expect(wrapper.find(`${BLOCK}__candidate-url--administration ${BLOCK}__test-candidate-button`).exists()).toBe(true);
            });

            it('should offer a test webhook only for the URLs PayPal and the shop know', async () => {
                const wrapper = await createWrapper({
                    webhook: () => Promise.resolve(createResult({
                        registration: 'invalid',
                        registeredUrl: null,
                        candidateUrls: [{ source: 'administration', url: 'https://shop.example/api/_action/paypal/webhook/execute?sw-token=<token>' }],
                        findings: [{ type: 'registration_invalid', severity: 'warning' }],
                    })),
                });
                await flushPromises();

                expect(wrapper.find(`${BLOCK}__test-registered-button`).exists()).toBe(false);
                expect(wrapper.find(`${BLOCK}__test-candidate-button`).exists()).toBe(true);
            });

            it('should send the test webhook to the Administration URL and name it', async () => {
                const wrapper = await createWrapper({ webhook: invalid });
                await flushPromises();

                await wrapper.find(`${BLOCK}__test-candidate-button`).trigger('click');
                await flushPromises();

                expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTest).toHaveBeenCalledWith(SALES_CHANNEL_ID, 'administration');
                expect(wrapper.find(`${BLOCK}__test`).find('.swag-paypal-settings-diagnostics-item__subtitle').text())
                    .toBe('swag-paypal-settings.diagnostics.webhook.labelAdministrationUrl');
                expect(wrapper.find(`${BLOCK}__test-registered-button`).attributes('disabled')).toBeDefined();
                expect(wrapper.find(`${BLOCK}__test-candidate-button`).attributes('disabled')).toBeDefined();

                for (let i = 0; i < 20; i += 1) {
                    jest.advanceTimersByTime(3000);
                    await flushPromises();
                }

                const missing = wrapper.find(`${BLOCK}__test`);
                expect(wrapper.vm.webhookTests[SALES_CHANNEL_ID]).toEqual({ status: 'missing', target: 'administration' });
                expect(missing.text()).toContain('swag-paypal-settings.diagnostics.webhook.test.missing.descriptionCandidate');
                expect(missing.find('.swag-paypal-settings-diagnostics-item__subtitle').text())
                    .toBe('swag-paypal-settings.diagnostics.webhook.labelAdministrationUrl');
                expect(wrapper.find(`${BLOCK}__test-candidate-button`).attributes('disabled')).toBeUndefined();
            });

            it('should send the test webhook to the registered URL', async () => {
                const wrapper = await createWrapper({ webhook: invalid });
                await flushPromises();

                await wrapper.find(`${BLOCK}__test-registered-button`).trigger('click');
                await flushPromises();

                expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTest).toHaveBeenCalledWith(SALES_CHANNEL_ID, 'registered');
            });
        });

        it('should stop polling when the component unmounts', async () => {
            const wrapper = await createWrapper();
            await flushPromises();

            await wrapper.find(`${BLOCK}__test-button`).trigger('click');
            await flushPromises();

            jest.advanceTimersByTime(3000);
            await flushPromises();
            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTestArrival).toHaveBeenCalledTimes(1);

            wrapper.unmount();

            jest.advanceTimersByTime(60000);
            await flushPromises();
            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTestArrival).toHaveBeenCalledTimes(1);
        });
    });

    describe('registration', () => {
        // i18n-t renders the link only for a message with its placeholder
        beforeAll(() => {
            const i18n = config.global.plugins.find((plugin) => 'global' in plugin) as unknown as {
                global: { mergeLocaleMessage: (locale: string, messages: Record<string, string>) => void };
            };
            i18n.global.mergeLocaleMessage('en', {
                'swag-paypal-settings.diagnostics.webhook.findings.registration_missing.summary': 'Register it in the {advancedTab}.',
                'swag-paypal-settings.diagnostics.webhook.findings.registration_invalid.description': 'Then register it in the {advancedTab}.',
            });
        });

        function createRegistrationResult(registration: 'missing' | 'invalid', withAppUrl = true) {
            return createResult({
                registration,
                registeredUrl: registration === 'invalid' ? 'https://old.example/webhook?sw-token=<token>' : null,
                candidateUrls: [
                    { source: 'administration', url: 'https://shop.example/webhook?sw-token=<token>' },
                    ...(withAppUrl ? [{ source: 'app_url' as const, url: 'https://app.example/webhook?sw-token=<token>' }] : []),
                ],
                findings: [{ type: `registration_${registration}`, severity: registration === 'missing' ? 'error' : 'warning' }],
            });
        }

        it('should keep a missing registration flat and link to the Advanced tab', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createRegistrationResult('missing')) });
            await flushPromises();

            const finding = wrapper.find(`${BLOCK}__finding`);
            expect(finding.element.tagName).toBe('DIV');
            expect(finding.find('.swag-paypal-settings-diagnostics-item__content').exists()).toBe(false);
            expect(wrapper.find(`${BLOCK}__candidate-url--administration`).exists()).toBe(false);

            const link = finding.findComponent<{ props: () => { to: unknown } }>('.swag-paypal-settings-diagnostics-item__subtitle .mt-link');
            expect(link.props('to')).toEqual({ name: 'swag.paypal.settings.index.advanced' });
            expect(link.text()).toBe('swag-paypal-settings.diagnostics.advancedTab');
        });

        it('should show where a registration for another URL could point and link to the Advanced tab', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createRegistrationResult('invalid')) });
            await flushPromises();

            expect(wrapper.find(`${BLOCK}__candidate-url--administration code`).text()).toBe('https://shop.example/webhook?sw-token=<token>');
            expect(wrapper.find(`${BLOCK}__candidate-url--app_url code`).text()).toBe('https://app.example/webhook?sw-token=<token>');

            const link = wrapper.findComponent<{ props: () => { to: unknown } }>('.swag-paypal-settings-diagnostics-item__content .mt-link');
            expect(link.props('to')).toEqual({ name: 'swag.paypal.settings.index.advanced' });
        });

        it.each(['missing', 'invalid'] as const)('should not offer to register a %s registration', async (registration) => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createRegistrationResult(registration)) });
            await flushPromises();

            expect(wrapper.find(`${BLOCK}__register-button`).exists()).toBe(false);
            expect(wrapper.find(`${BLOCK}__refresh-button`).exists()).toBe(false);
            expect(wrapper.text()).not.toContain('buttonRegister');
        });

        it('should offer a test webhook for each candidate of an invalid registration', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createRegistrationResult('invalid')) });
            await flushPromises();

            expect(wrapper.findAll(`${BLOCK}__test-candidate-button`)).toHaveLength(2);
        });

        it('should send the test webhook to the APP_URL and name it', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const wrapper = await createWrapper({ webhook: () => Promise.resolve(createRegistrationResult('invalid')) });
            await flushPromises();

            await wrapper.find(`${BLOCK}__candidate-url--app_url ${BLOCK}__test-candidate-button`).trigger('click');
            await flushPromises();

            expect(wrapper.vm.SwagPayPalDiagnosticsService.webhookTest).toHaveBeenCalledWith(SALES_CHANNEL_ID, 'app_url');
            expect(wrapper.find(`${BLOCK}__test`).find('.swag-paypal-settings-diagnostics-item__subtitle').text())
                .toBe('swag-paypal-settings.diagnostics.webhook.labelAppUrl');
        });
    });
});

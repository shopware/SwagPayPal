import { mount } from '@vue/test-utils';
import type * as PayPal from 'SwagPayPal/types';
import SwagPayPalSettingsDiagnosticsItem from '../swag-paypal-settings-diagnostics-item';
import SwagPayPalSettingsDiagnosticsSection from '../swag-paypal-settings-diagnostics-section';
import SwagPayPalSettingsDiagnosticsReachability from '.';

Shopware.Component.register('swag-paypal-settings-diagnostics-item', Promise.resolve(SwagPayPalSettingsDiagnosticsItem));
Shopware.Component.register('swag-paypal-settings-diagnostics-section', Promise.resolve(SwagPayPalSettingsDiagnosticsSection));
Shopware.Component.register('swag-paypal-settings-diagnostics-reachability', Promise.resolve(SwagPayPalSettingsDiagnosticsReachability));

type ReachabilityResult = PayPal.Diagnostics<'reachability'>;

const BLOCK = '.swag-paypal-settings-diagnostics-reachability';
const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';

function itemStatus(classes: string[]): string | undefined {
    return classes.find((cls) => cls.startsWith('swag-paypal-settings-diagnostics-item--'));
}

function createResult(result: Partial<ReachabilityResult> = {}): ReachabilityResult {
    return {
        url: 'https://shop.example/api',
        error: null,
        severity: null,
        addresses: ['93.184.216.34'],
        statusCode: null,
        location: null,
        detail: null,
        ...result,
    };
}

async function createWrapper(
    props: Record<string, unknown> = {},
) {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-reachability') as typeof SwagPayPalSettingsDiagnosticsReachability,
        {
            props: { subject: 'callback', ...props },
            global: {
                stubs: {
                    'swag-paypal-settings-diagnostics-item': await Shopware.Component.build('swag-paypal-settings-diagnostics-item'),
                    'swag-paypal-settings-diagnostics-section': await Shopware.Component.build('swag-paypal-settings-diagnostics-section'),
                },
            },
        },
    );
}

describe('swag-paypal-settings-diagnostics-reachability', () => {
    it('should show the test running', async () => {
        const wrapper = await createWrapper({ test: { status: 'testing' } });

        expect(wrapper.find(`${BLOCK}__testing`).exists()).toBe(true);
        expect(wrapper.text()).toContain('swag-paypal-settings.diagnostics.reachability.title');
        expect(wrapper.text()).toContain('swag-paypal-settings.diagnostics.reachability.description');
    });

    it('should show a failed test as a critical item', async () => {
        const wrapper = await createWrapper({ test: { status: 'failed' } });

        expect(itemStatus(wrapper.find(`${BLOCK}__failed`).classes())).toBe('swag-paypal-settings-diagnostics-item--error');
    });

    it('should render no section when there is no URL to test', async () => {
        const wrapper = await createWrapper({ test: { status: 'done', results: [] } });

        expect(wrapper.find(BLOCK).exists()).toBe(false);
        expect(wrapper.text()).toBe('');
    });

    it('should summarize the endpoints by their worst result', async () => {
        const results = [
            createResult({ url: 'https://a.example/api', error: 'host_local', severity: 'error' }),
            createResult({ url: 'https://c.example/api', error: 'host_local', severity: 'error' }),
            createResult({ url: 'https://b.example/api', error: 'host_private', severity: 'warning' }),
            createResult({ url: 'https://d.example/api' }),
        ];
        const wrapper = await createWrapper({ test: { status: 'done', results } });

        const summary = wrapper.find(`${BLOCK}__summary`);
        expect(itemStatus(summary.classes())).toBe('swag-paypal-settings-diagnostics-item--error');
        expect(summary.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.reachability.summary.callback.error');
        expect(summary.find('.swag-paypal-settings-diagnostics-item__subtitle').text()).toBe([
            'swag-paypal-settings.diagnostics.reachability.counts.errors.host_local',
            'swag-paypal-settings.diagnostics.reachability.counts.errors.host_private',
            'swag-paypal-settings.diagnostics.reachability.counts.reachable',
        ].join(' · '));
        expect(summary.text()).toContain('swag-paypal-settings.diagnostics.reachability.summary.callback.description');
        expect(summary.text()).toContain('swag-paypal-settings.diagnostics.reachability.summary.action.error');
        expect(wrapper.find(`${BLOCK}__results-summary`).text())
            .toContain('swag-paypal-settings.diagnostics.reachability.viewResults');
        expect(wrapper.find(`${BLOCK}__results`).attributes('open')).toBeUndefined();
    });

    it('should name the webhook in the summary of a webhook', async () => {
        const results = [createResult({ error: 'timeout', severity: 'warning' })];
        const wrapper = await createWrapper({ subject: 'webhook', test: { status: 'done', results } });

        const summary = wrapper.find(`${BLOCK}__summary`);
        expect(itemStatus(summary.classes())).toBe('swag-paypal-settings-diagnostics-item--warning');
        expect(summary.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.reachability.summary.webhook.warning');
        expect(summary.element.tagName).toBe('DETAILS');
        expect(summary.text()).toContain('swag-paypal-settings.diagnostics.reachability.summary.action.warning');
    });

    it('should pass when every endpoint is reachable', async () => {
        const results = [createResult(), createResult({ url: 'https://b.example/api' })];
        const wrapper = await createWrapper({ test: { status: 'done', results } });

        const summary = wrapper.find(`${BLOCK}__summary`);
        expect(itemStatus(summary.classes())).toBe('swag-paypal-settings-diagnostics-item--passed');
        expect(summary.find('.swag-paypal-settings-diagnostics-item__title').text())
            .toBe('swag-paypal-settings.diagnostics.reachability.summary.callback.passed');
        expect(summary.element.tagName).toBe('DIV');
        expect(summary.find('.swag-paypal-settings-diagnostics-item__chevron').exists()).toBe(false);
    });

    it('should show an item per result, as the URLs come', async () => {
        const results: ReachabilityResult[] = [
            {
                url: 'https://localhost/api',
                error: 'host_local',
                severity: 'error',
                addresses: ['127.0.0.1'],
                statusCode: 400,
                location: null,
                detail: null,
            },
            {
                url: 'http://shop.example/paypal/express/shipping-callback/<token>',
                error: 'redirected',
                severity: 'error',
                addresses: [],
                statusCode: 301,
                location: 'https://shop.example/maintenance',
                detail: null,
            },
            {
                url: 'https://private.example/api',
                error: 'host_private',
                severity: 'warning',
                addresses: ['10.0.0.5'],
                statusCode: null,
                location: null,
                detail: null,
            },
            {
                url: 'https://selfsigned.example/api',
                error: 'certificate_invalid',
                severity: 'error',
                addresses: ['93.184.216.34'],
                statusCode: null,
                location: null,
                detail: 'SSL certificate OpenSSL verify result: self-signed certificate (18)',
            },
            {
                url: `https://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`,
                error: null,
                severity: null,
                addresses: ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'],
                statusCode: 400,
                location: null,
                detail: null,
            },
        ];

        const wrapper = await createWrapper({ test: { status: 'done', results } });

        const items = wrapper.findAll(`${BLOCK}__result`);
        expect(items.map((item) => itemStatus(item.classes()))).toEqual([
            'swag-paypal-settings-diagnostics-item--error',
            'swag-paypal-settings-diagnostics-item--error',
            'swag-paypal-settings-diagnostics-item--warning',
            'swag-paypal-settings-diagnostics-item--error',
            'swag-paypal-settings-diagnostics-item--passed',
        ]);
        expect(items.map((item) => item.find('.swag-paypal-settings-diagnostics-item__title').text())).toEqual([
            'swag-paypal-settings.diagnostics.reachability.errors.host_local.title',
            'swag-paypal-settings.diagnostics.reachability.errors.redirected.title',
            'swag-paypal-settings.diagnostics.reachability.errors.host_private.title',
            'swag-paypal-settings.diagnostics.reachability.errors.certificate_invalid.title',
            'swag-paypal-settings.diagnostics.reachability.reachable.title',
        ]);
        expect(items[0].text()).toContain('swag-paypal-settings.diagnostics.reachability.errors.host_local.description');

        expect(items[4].find('.swag-paypal-settings-diagnostics-item__subtitle').text())
            .toBe(`https://shop.example/paypal/express/shipping-callback/${SALES_CHANNEL_ID}/<token>`);
        expect(items[4].element.tagName).toBe('DIV');
        expect(items[4].find('.swag-paypal-settings-diagnostics-item__content').exists()).toBe(false);
        expect(items.slice(0, 4).map((item) => item.element.tagName)).toEqual(['DETAILS', 'DETAILS', 'DETAILS', 'DETAILS']);
        expect(items[0].find(`${BLOCK}__url`).exists()).toBe(false);

        expect(items[0].text()).toContain('swag-paypal-settings.diagnostics.reachability.labelStatusCode');

        expect(items[1].text()).toContain('301');
        expect(items[1].text()).toContain('https://shop.example/maintenance');
        expect(items[1].find(`${BLOCK}__addresses`).text()).toBe('swag-paypal-settings.diagnostics.reachability.noAddresses');

        expect(items[2].text()).not.toContain('swag-paypal-settings.diagnostics.reachability.labelStatusCode');

        expect(items[3].text()).toContain('swag-paypal-settings.diagnostics.reachability.labelDetail');
        const codes = items[3].findAll('code');
        expect(codes[codes.length - 1].text()).toBe('SSL certificate OpenSSL verify result: self-signed certificate (18)');
    });
});

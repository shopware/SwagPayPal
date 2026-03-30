import { mount } from '@vue/test-utils';
import SwagPayPalSettingsWebhook from '.';

Shopware.Component.register('swag-paypal-settings-webhook', Promise.resolve(SwagPayPalSettingsWebhook));

async function createWrapper() {
    return mount(
        await Shopware.Component.build('swag-paypal-settings-webhook') as typeof SwagPayPalSettingsWebhook,
        {
            global: {
                mocks: { $tc: (key: string) => key },
                provide: {
                    SwagPayPalWebhookService: {
                        status: jest.fn(() => Promise.resolve({ result: null })),
                        register: jest.fn(() => Promise.resolve()),
                    },
                    SwagPayPalSettingsService: {},
                    settingsStoreSavingSettings: Shopware.Vue.ref('none'),
                },
                stubs: {
                    'sw-label': await wrapTestComponent('sw-label', { sync: true }),
                    'sw-color-badge': await wrapTestComponent('sw-color-badge', { sync: true }),
                },
            },
        },
    );
}

describe('swag-paypal-settings-webhook', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should fetch status on creation', async () => {
        const wrapper = await createWrapper();

        expect(wrapper.vm.SwagPayPalWebhookService.status).toHaveBeenCalled();
    });

    it('should pick correct status variant', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.webhookStatusVariant).toBe('success');

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.webhookStatusVariant).toBe('danger');

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.webhookStatusVariant).toBe('warning');

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.webhookStatusVariant).toBe('neutral');

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.webhookStatusVariant).toBe('neutral');
    });

    it('should allow refresh', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.allowRefresh).toBe(false);

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.allowRefresh).toBe(true);

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.allowRefresh).toBe(true);

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.allowRefresh).toBe(false);

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.allowRefresh).toBe(false);
    });

    it('should have correct status label', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.allWebhookStatus.null = 'valid';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.valid');

        wrapper.vm.allWebhookStatus.null = 'missing';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.missing');

        wrapper.vm.allWebhookStatus.null = 'invalid';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.invalid');

        wrapper.vm.allWebhookStatus.null = '';
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');

        wrapper.vm.allWebhookStatus.null = undefined;
        expect(wrapper.vm.webhookStatusLabel).toBe('swag-paypal-settings.webhook.status.unknown');
    });

    it('should fetch webhook status', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');

        wrapper.vm.fetchWebhookStatus(null);

        expect(wrapper.vm.status).toBe('fetching');
        expect(spyStatus).toHaveBeenCalled();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.status).toBe('none');
    });

    it('should refresh webhook', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');
        const spyRegister = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'register');

        wrapper.vm.onRefreshWebhook();

        expect(wrapper.vm.status).toBe('refreshing');
        expect(spyRegister).toHaveBeenCalled();

        await flushPromises();

        expect(wrapper.vm.status).toBe('none');

        await wrapper.vm.$nextTick();

        expect(spyStatus).toHaveBeenCalled();
    });

    it('should refresh webhook with error', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.createNotificationError = jest.fn();

        wrapper.vm.SwagPayPalWebhookService.register = jest.fn(() => Promise.reject({ response: {} }));

        wrapper.vm.onRefreshWebhook();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.status).toBe('refreshing');
        expect(wrapper.vm.createNotificationError).toHaveBeenCalled();
    });

    it('should fetch webhook status on save', async () => {
        const wrapper = await createWrapper();

        const spyFetch = jest.spyOn(wrapper.vm, 'fetchWebhookStatus');

        // @ts-expect-error - property does exist, it got injected
        wrapper.vm.settingsStoreSavingSettings = 'success';

        await wrapper.vm.$nextTick();

        expect(spyFetch).toHaveBeenCalled();
    });
});

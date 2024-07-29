import { mount } from '@vue/test-utils';
import 'SwagPayPal/module/swag-paypal/components/swag-paypal-webhook';

Shopware.Component.register('swag-paypal-webhook', () => import('.'));

async function createWrapper(customOptions = {}) {
    const options = {
        global: {
            mocks: { $tc: (key) => key },
            provide: {
                acl: {
                    can: () => true,
                },
                SwagPayPalWebhookService: {
                    status: () => Promise.resolve({ result: null }),
                    register: () => Promise.resolve(),
                },
            },
            stubs: {
                'sw-button': await wrapTestComponent('sw-button', { sync: true }),
                'sw-card': await wrapTestComponent('sw-card', { sync: true }),
                'sw-card-deprecated': await wrapTestComponent('sw-card-deprecated', { sync: true }),
                'sw-label': await wrapTestComponent('sw-label', { sync: true }),
            },
        },
        props: { selectedSalesChannelId: 'SALES_CHANNEL' },
    };

    return mount(
        await Shopware.Component.build('swag-paypal-webhook'),
        Shopware.Utils.object.mergeWith(options, customOptions),
    );
}

describe('swag-paypal-webhook', () => {
    it('should be a Vue.js component', async () => {
        const wrapper = await createWrapper();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should fetch status on creation', async () => {
        const spyStatus = jest.fn(() => Promise.resolve({ result: 'valid' }));

        await createWrapper({
            global: {
                provide: {
                    SwagPayPalWebhookService: {
                        status: spyStatus,
                    },
                },
            },
        });

        expect(spyStatus).toBeCalled();
    });

    it('should pick correct status variant', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.webhookStatus = 'valid';
        expect(wrapper.vm.webhookStatusVariant).toEqual('success');

        wrapper.vm.webhookStatus = 'missing';
        expect(wrapper.vm.webhookStatusVariant).toEqual('danger');

        wrapper.vm.webhookStatus = 'invalid';
        expect(wrapper.vm.webhookStatusVariant).toEqual('warning');

        wrapper.vm.webhookStatus = '';
        expect(wrapper.vm.webhookStatusVariant).toEqual('neutral');

        wrapper.vm.webhookStatus = null;
        expect(wrapper.vm.webhookStatusVariant).toEqual('neutral');
    });

    it('should allow refresh', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.webhookStatus = 'valid';
        expect(wrapper.vm.allowRefresh).toEqual(false);

        wrapper.vm.webhookStatus = 'missing';
        expect(wrapper.vm.allowRefresh).toEqual(true);

        wrapper.vm.webhookStatus = 'invalid';
        expect(wrapper.vm.allowRefresh).toEqual(true);

        wrapper.vm.webhookStatus = '';
        expect(wrapper.vm.allowRefresh).toEqual(false);

        wrapper.vm.webhookStatus = null;
        expect(wrapper.vm.allowRefresh).toEqual(false);
    });

    it('should have correct status label', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.webhookStatus = 'valid';
        expect(wrapper.vm.webhookStatusLabel).toEqual('swag-paypal.webhook.status.valid');

        wrapper.vm.webhookStatus = 'missing';
        expect(wrapper.vm.webhookStatusLabel).toEqual('swag-paypal.webhook.status.missing');

        wrapper.vm.webhookStatus = 'invalid';
        expect(wrapper.vm.webhookStatusLabel).toEqual('swag-paypal.webhook.status.invalid');

        wrapper.vm.webhookStatus = '';
        expect(wrapper.vm.webhookStatusLabel).toEqual('swag-paypal.webhook.status.unknown');

        wrapper.vm.webhookStatus = null;
        expect(wrapper.vm.webhookStatusLabel).toEqual('swag-paypal.webhook.status.unknown');
    });

    it('should fetch webhook status', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');

        wrapper.vm.fetchWebhookStatus();

        expect(wrapper.vm.isFetchingStatus).toBe(true);
        expect(spyStatus).toBeCalled();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.isFetchingStatus).toBe(false);
    });

    it('should refresh webhook', async () => {
        const wrapper = await createWrapper();

        const spyStatus = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'status');
        const spyRegister = jest.spyOn(wrapper.vm.SwagPayPalWebhookService, 'register');

        wrapper.vm.onRefreshWebhook();

        expect(wrapper.vm.isRefreshing).toBe(true);
        expect(spyRegister).toBeCalled();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.isFetchingStatus).toBe(false);

        await wrapper.vm.$nextTick();

        expect(spyStatus).toBeCalled();
    });

    it('should refresh webhook with error', async () => {
        const wrapper = await createWrapper();

        wrapper.vm.createNotificationError = jest.fn();
        // eslint-disable-next-line prefer-promise-reject-errors
        wrapper.vm.SwagPayPalWebhookService.register = jest.fn(() => Promise.reject({ response: {} }));

        wrapper.vm.onRefreshWebhook();

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.isRefreshing).toBe(true);
        expect(wrapper.vm.createNotificationError).toBeCalled();
    });
});

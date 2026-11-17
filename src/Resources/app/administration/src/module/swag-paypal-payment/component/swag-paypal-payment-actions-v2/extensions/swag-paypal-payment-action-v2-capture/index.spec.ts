import { flushPromises, mount } from '@vue/test-utils';
import SwagPayPalPaymentActionV2Capture from '.';
import {
    ORDER_CAPTURE_COMPLETED,
    ORDER_CAPTURE_DECLINED,
    ORDER_CAPTURE_FAILED,
    ORDER_CAPTURE_PENDING,
} from '../../../swag-paypal-payment-details-v2/swag-paypal-order-consts';

Shopware.Component.register('swag-paypal-payment-action-v2-capture', Promise.resolve(SwagPayPalPaymentActionV2Capture));

async function createWrapper(captureAuthorization: jest.Mock) {
    return mount(
        await Shopware.Component.build('swag-paypal-payment-action-v2-capture') as typeof SwagPayPalPaymentActionV2Capture,
        {
            props: {
                paypalOrder: {
                    id: 'paypal-order-id',
                    purchase_units: [{
                        payments: {
                            authorizations: [{
                                id: 'authorization-id',
                                amount: {
                                    currency_code: 'EUR',
                                    value: '100.00',
                                },
                            }],
                        },
                    }],
                },
                orderTransactionId: 'order-transaction-id',
                paypalPartnerAttributionId: 'partner-attribution-id',
                captureableAmount: 100,
            },
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
                provide: {
                    SwagPayPalOrderService: {
                        captureAuthorization,
                    },
                },
                stubs: {
                    'sw-modal': true,
                    'mt-text-field': true,
                    'mt-number-field': true,
                    'mt-textarea': true,
                    'mt-checkbox': true,
                    'mt-banner': true,
                    'mt-button': true,
                    'sw-loader': true,
                },
            },
        },
    );
}

describe('swag-paypal-payment-action-v2-capture', () => {
    it.each([
        [ORDER_CAPTURE_COMPLETED, 'createNotificationSuccess', 'successMessage'],
        [ORDER_CAPTURE_PENDING, 'createNotificationInfo', 'pendingMessage'],
        [ORDER_CAPTURE_DECLINED, 'createNotificationError', 'failedMessage'],
        [ORDER_CAPTURE_FAILED, 'createNotificationError', 'failedMessage'],
    ])('should notify about a %s capture', async (status, notification, message) => {
        const wrapper = await createWrapper(jest.fn(() => Promise.resolve({ status })));
        wrapper.vm.createNotificationSuccess = jest.fn();
        wrapper.vm.createNotificationInfo = jest.fn();
        wrapper.vm.createNotificationError = jest.fn();

        (wrapper.vm as unknown as { capture: () => void }).capture();
        await flushPromises();

        expect(wrapper.vm[notification]).toHaveBeenCalledWith(expect.objectContaining({
            message: `swag-paypal-payment.captureAction.${message}`,
        }));
        expect(wrapper.emitted('modal-close')).toHaveLength(1);
    });
});

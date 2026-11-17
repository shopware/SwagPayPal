import { flushPromises, mount } from '@vue/test-utils';
import SwagPayPalPaymentActionV2Refund from '.';
import {
    ORDER_CAPTURE_COMPLETED,
    ORDER_REFUND_CANCELLED,
    ORDER_REFUND_COMPLETED,
    ORDER_REFUND_FAILED,
    ORDER_REFUND_PENDING,
} from '../../../swag-paypal-payment-details-v2/swag-paypal-order-consts';

Shopware.Component.register('swag-paypal-payment-action-v2-refund', Promise.resolve(SwagPayPalPaymentActionV2Refund));

async function createWrapper(refundCapture: jest.Mock) {
    return mount(
        await Shopware.Component.build('swag-paypal-payment-action-v2-refund') as typeof SwagPayPalPaymentActionV2Refund,
        {
            props: {
                paypalOrder: {
                    id: 'paypal-order-id',
                    purchase_units: [{
                        payments: {
                            captures: [{
                                id: 'capture-id',
                                status: ORDER_CAPTURE_COMPLETED,
                                amount: {
                                    currency_code: 'EUR',
                                    value: '100.00',
                                },
                                create_time: '2020-08-17T13:09:30Z',
                                update_time: '2020-08-17T13:09:30Z',
                            }],
                        },
                    }],
                },
                orderTransactionId: 'order-transaction-id',
                paypalPartnerAttributionId: 'partner-attribution-id',
                refundableAmount: 100,
            },
            global: {
                mocks: {
                    $tc: (key: string) => key,
                },
                provide: {
                    SwagPayPalOrderService: {
                        refundCapture,
                    },
                },
                stubs: {
                    'sw-modal': true,
                    'sw-select-field': true,
                    'sw-number-field': true,
                    'swag-paypal-text-field': true,
                    'swag-paypal-textarea-field': true,
                    'sw-button': true,
                    'sw-loader': true,
                },
            },
        },
    );
}

describe('swag-paypal-payment-action-v2-refund', () => {
    it.each([
        [ORDER_REFUND_COMPLETED, 'createNotificationSuccess', 'successMessage'],
        [ORDER_REFUND_PENDING, 'createNotificationInfo', 'pendingMessage'],
        [ORDER_REFUND_FAILED, 'createNotificationError', 'failedMessage'],
        [ORDER_REFUND_CANCELLED, 'createNotificationError', 'failedMessage'],
    ])('should notify about a %s refund', async (status, notification, message) => {
        const wrapper = await createWrapper(jest.fn(() => Promise.resolve({ status })));
        wrapper.vm.createNotificationSuccess = jest.fn();
        wrapper.vm.createNotificationInfo = jest.fn();
        wrapper.vm.createNotificationError = jest.fn();

        (wrapper.vm as unknown as { refund: () => void }).refund();
        await flushPromises();

        expect(wrapper.vm[notification]).toHaveBeenCalledWith(expect.objectContaining({
            message: `swag-paypal-payment.refundAction.${message}`,
        }));
        expect(wrapper.emitted('modal-close')).toHaveLength(1);
    });
});

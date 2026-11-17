import { shallowMount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import 'src/app/mixin/notification.mixin';
import 'SwagPayPal/module/swag-paypal-payment/component/swag-paypal-payment-actions-v2/extensions/swag-paypal-payment-action-v2-capture';
import {
    ORDER_CAPTURE_COMPLETED,
    ORDER_CAPTURE_DECLINED,
    ORDER_CAPTURE_FAILED,
    ORDER_CAPTURE_PENDING,
} from '../../../swag-paypal-payment-details-v2/swag-paypal-order-consts';

async function createWrapper(captureAuthorization) {
    return shallowMount(await Shopware.Component.build('swag-paypal-payment-action-v2-capture'), {
        propsData: {
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
        mocks: {
            $tc: (key) => key,
        },
        provide: {
            SwagPayPalOrderService: {
                captureAuthorization,
            },
        },
    });
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

        wrapper.vm.capture();
        await flushPromises();

        expect(wrapper.vm[notification]).toHaveBeenCalledWith(expect.objectContaining({
            message: `swag-paypal-payment.captureAction.${message}`,
        }));
        expect(wrapper.emitted('modal-close')).toHaveLength(1);
    });
});

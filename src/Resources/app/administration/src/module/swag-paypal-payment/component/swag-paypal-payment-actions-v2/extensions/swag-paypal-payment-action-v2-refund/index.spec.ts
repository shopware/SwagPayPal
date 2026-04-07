import { mount } from '@vue/test-utils';
import SwagPayPalPaymentActionV2Refund from '.';
import {
    ORDER_CAPTURE_COMPLETED,
    ORDER_CAPTURE_DECLINED,
    ORDER_CAPTURE_FAILED,
    ORDER_CAPTURE_PARTIALLY_REFUNDED,
    ORDER_CAPTURE_PENDING,
    ORDER_CAPTURE_REFUNDED,
} from '../../../swag-paypal-payment-details-v2/swag-paypal-order-consts';

Shopware.Component.register('swag-paypal-payment-action-v2-refund', Promise.resolve(SwagPayPalPaymentActionV2Refund));

function createCapture(id: string, status: string, value = '100.00') {
    return {
        id,
        status,
        amount: {
            currency_code: 'EUR',
            value,
        },
        create_time: '2020-08-17T13:09:30Z',
        update_time: '2020-08-17T13:09:30Z',
    };
}

function createPayPalOrder(captures: Array<Record<string, unknown>>) {
    return {
        id: 'paypal-order-id',
        purchase_units: [{
            payments: {
                captures,
            },
        }],
    };
}

async function createWrapper(
    captures: Array<Record<string, unknown>>,
    refundableAmount = 200,
) {
    return mount(
        await Shopware.Component.build('swag-paypal-payment-action-v2-refund') as typeof SwagPayPalPaymentActionV2Refund,
        {
        props: {
            paypalOrder: createPayPalOrder(captures),
            orderTransactionId: 'order-transaction-id',
            paypalPartnerAttributionId: 'partner-attribution-id',
            refundableAmount,
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            provide: {
                SwagPayPalOrderService: {
                    refundCapture: jest.fn(),
                },
            },
            stubs: {
                'sw-modal': true,
                'mt-select': true,
                'mt-text-field': true,
                'mt-number-field': true,
                'mt-textarea': true,
                'mt-button': true,
                'sw-loader': true,
            },
        },
        },
    );
}

describe('swag-paypal-payment-action-v2-refund', () => {
    it('should only keep refundable captures selectable', async () => {
        const wrapper = await createWrapper([
            createCapture('completed', ORDER_CAPTURE_COMPLETED),
            createCapture('partially-refunded', ORDER_CAPTURE_PARTIALLY_REFUNDED, '25.00'),
            createCapture('declined', ORDER_CAPTURE_DECLINED),
            createCapture('failed', ORDER_CAPTURE_FAILED),
            createCapture('pending', ORDER_CAPTURE_PENDING),
            createCapture('refunded', ORDER_CAPTURE_REFUNDED),
        ]);

        const captures = wrapper.vm.captures as Array<{ id: string }>;

        expect(captures.map(({ id }) => id)).toStrictEqual([
            'completed',
            'partially-refunded',
        ]);
        expect(wrapper.vm.selectedCaptureId).toBe('completed');
    });

    it('should initialize safely when no refundable captures are available', async () => {
        const wrapper = await createWrapper([
            createCapture('declined', ORDER_CAPTURE_DECLINED),
            createCapture('failed', ORDER_CAPTURE_FAILED),
            createCapture('pending', ORDER_CAPTURE_PENDING),
            createCapture('refunded', ORDER_CAPTURE_REFUNDED),
        ]);

        expect(wrapper.vm.captures).toStrictEqual([]);
        expect(wrapper.vm.selectedCaptureId).toBe('');
        expect(wrapper.vm.refundAmount).toBe(0);
        expect(wrapper.vm.refundableAmountForSelectedCapture).toBe(0);
    });
});

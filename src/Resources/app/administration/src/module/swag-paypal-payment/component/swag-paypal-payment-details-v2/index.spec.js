import { shallowMount } from '@vue/test-utils';
import 'SwagPayPal/module/swag-paypal-payment/component/swag-paypal-payment-details-v2';

function createPayment(id, status, value, createTime) {
    return {
        id,
        status,
        amount: {
            currency_code: 'EUR',
            value,
        },
        create_time: createTime,
        update_time: createTime,
    };
}

function createPayPalOrder(captures, refunds = []) {
    return {
        id: 'paypal-order-id',
        intent: 'CAPTURE',
        status: 'COMPLETED',
        create_time: '2020-08-17T13:09:30Z',
        update_time: '2020-08-17T13:09:30Z',
        payer: {
            payer_id: 'payer-id',
        },
        purchase_units: [{
            amount: {
                currency_code: 'EUR',
                value: '100.00',
            },
            payments: {
                captures,
                refunds,
            },
        }],
    };
}

async function createWrapper(paypalOrder) {
    if (!Shopware.Filter.getByName('date')) {
        Shopware.Filter.register('date', (value) => value);
    }

    return shallowMount(await Shopware.Component.build('swag-paypal-payment-details-v2'), {
        propsData: {
            paypalOrder,
            orderTransaction: {
                customFields: {},
            },
        },
        mocks: {
            $tc: (key) => key,
        },
        stubs: {
            'sw-card': true,
            'sw-card-section': true,
            'swag-paypal-payment-actions-v2': true,
            'sw-description-list': true,
            'swag-paypal-pui-details': true,
            'sw-data-grid': true,
        },
    });
}

describe('swag-paypal-payment-details-v2', () => {
    it.each([
        ['COMPLETED', 2.15],
        ['PENDING', 2.15],
        ['FAILED', 52.97],
        ['CANCELLED', 52.97],
    ])('should compute the refundable amount with a %s refund', async (refundStatus, expectedRefundableAmount) => {
        const wrapper = await createWrapper(createPayPalOrder(
            [createPayment('capture-id', 'COMPLETED', '52.97', '2020-08-17T13:09:30Z')],
            [createPayment('refund-id', refundStatus, '50.82', '2020-08-18T13:09:30Z')],
        ));

        expect(wrapper.vm.refundableAmount).toBe(expectedRefundableAmount);
        expect(wrapper.vm.payments).toHaveLength(2);
        expect(wrapper.vm.payments[1]).toMatchObject({
            id: 'refund-id',
            status: refundStatus,
        });
    });

    it('should deduct only the completed refund when another one failed', async () => {
        const wrapper = await createWrapper(createPayPalOrder(
            [createPayment('capture-id', 'COMPLETED', '52.97', '2020-08-17T13:09:30Z')],
            [
                createPayment('failed-refund-id', 'FAILED', '50.82', '2020-08-18T13:09:30Z'),
                createPayment('completed-refund-id', 'COMPLETED', '10.00', '2020-08-19T13:09:30Z'),
            ],
        ));

        expect(wrapper.vm.refundableAmount).toBe(42.97);
    });
});

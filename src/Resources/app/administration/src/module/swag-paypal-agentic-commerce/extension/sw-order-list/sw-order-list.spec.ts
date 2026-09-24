import SwOrderListExtension from '.';
import { PAYPAL_AGENTIC_COMMERCE_ORDER_CUSTOM_FIELD } from 'SwagPayPal/constant/swag-paypal.constant';

type OrderListMethods = {
    isAgenticCommerceOrder: (order: TEntity<'order'>) => boolean;
};

const componentMethods = SwOrderListExtension.methods as unknown as OrderListMethods;

describe('sw-order-list', () => {
    it.each([
        [{ customFields: { [PAYPAL_AGENTIC_COMMERCE_ORDER_CUSTOM_FIELD]: 'agentic-sales-channel-id' } }, true],
        [{ customFields: { foo: 'bar' } }, false],
        [{ customFields: null }, false],
        [{}, false],
    ])('should detect agentic commerce orders (%j)', (order, expected) => {
        expect(componentMethods.isAgenticCommerceOrder(order as unknown as TEntity<'order'>)).toBe(expected);
    });
});

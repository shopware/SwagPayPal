import template from './sw-order-list.html.twig';
import './sw-order-list.scss';
import { PAYPAL_AGENTIC_COMMERCE_ORDER_CUSTOM_FIELD } from 'SwagPayPal/constant/swag-paypal.constant';

export default Shopware.Component.wrapComponentConfig({
    template,

    methods: {
        isAgenticCommerceOrder(order: TEntity<'order'>): boolean {
            return !!order.customFields?.[PAYPAL_AGENTIC_COMMERCE_ORDER_CUSTOM_FIELD];
        },
    },
});

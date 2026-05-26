import template from './sw-sales-channel-detail.html.twig';
import { PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isAgenticCommerceType(): boolean {
            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.salesChannel?.typeId === PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_TYPE_ID;
        },

        isProductComparison(): boolean {
            return this.isAgenticCommerceType || this.$super('isProductComparison') as boolean;
        },
    },
});

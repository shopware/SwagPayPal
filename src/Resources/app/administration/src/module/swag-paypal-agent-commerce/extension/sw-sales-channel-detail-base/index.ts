import template from './sw-sales-channel-detail-base.html.twig';
import { PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID } from "SwagPayPal/constant/swag-paypal.constant";

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isAgentCommerceType(): boolean {
            // @ts-expect-error - salesChannel is defined in the parent component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.salesChannel?.typeId === PAYPAL_AGENT_COMMERCE_SALES_CHANNEL_TYPE_ID;
        },

        isProductComparison(): boolean {
            return this.isAgentCommerceType || this.$super('isProductComparison') as boolean;
        },
    },
});

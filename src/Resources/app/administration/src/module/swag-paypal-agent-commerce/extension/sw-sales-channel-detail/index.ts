import template from './sw-sales-channel-detail.html.twig';
import {SwagPayPalDefaults} from "SwagPayPal/defaults";

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isAgentCommerceType(): boolean {
            // @ts-expect-error - salesChannel is defined in the parent component
            return this.salesChannel && this.salesChannel.typeId === SwagPayPalDefaults.agentCommerceTypeId;
        },

        isProductComparison(): boolean {
            return this.isAgentCommerceType || this.$super('isProductComparison') as boolean;
        },
    },
});

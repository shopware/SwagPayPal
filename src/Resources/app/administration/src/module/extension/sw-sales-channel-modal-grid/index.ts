import template from './sw-sales-channel-modal-grid.html.twig';
import './sw-sales-channel-modal-grid.scss';
import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';

// salesChannelTypes is from extended component - fake the existence of salesChannelTypes
export default Shopware.Component.wrapComponentConfig<{ salesChannelTypes: TEntityCollection<'sales_channel_type'> }>({
    template,

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId: string): boolean {
            const salesChannelType = this.salesChannelTypes.find(type => type.id === salesChannelTypeId);

            return salesChannelType?.id === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
});

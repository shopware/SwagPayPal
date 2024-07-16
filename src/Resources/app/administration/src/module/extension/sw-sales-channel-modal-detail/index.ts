import template from './sw-sales-channel-modal-detail.html.twig';
import './sw-sales-channel-modal-detail.scss';
import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';

export default Shopware.Component.wrapComponentConfig({
    template,

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId: string): boolean {
            return salesChannelTypeId === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
});

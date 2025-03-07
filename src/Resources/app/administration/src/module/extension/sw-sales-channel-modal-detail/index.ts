import template from './sw-sales-channel-modal-detail.html.twig';
import './sw-sales-channel-modal-detail.scss';
import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';
import paypalPosLogo from 'SwagPayPal/static/img/paypal-pos-logo.svg';

export default Shopware.Component.wrapComponentConfig({
    template,

    data() {
        return { paypalPosLogo };
    },

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId: string): boolean {
            return salesChannelTypeId === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },
});

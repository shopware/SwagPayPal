import template from './sw-sales-channel-modal-grid.html.twig';
import './sw-sales-channel-modal-grid.scss';
import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';

const { Component } = Shopware;

Component.override('sw-sales-channel-modal-grid', {
    template,

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId) {
            const salesChannelType = this.salesChannelTypes.find(type => type.id === salesChannelTypeId);

            return salesChannelType.id === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
});

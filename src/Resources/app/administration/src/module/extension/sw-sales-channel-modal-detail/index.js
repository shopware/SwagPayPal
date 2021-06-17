import template from './sw-sales-channel-modal-detail.html.twig';
import './sw-sales-channel-modal-detail.scss';
import { PAYPAL_POS_SALES_CHANNEL_TYPE_ID } from '../../../constant/swag-paypal.constant';

const { Component } = Shopware;

Component.override('sw-sales-channel-modal-detail', {
    template,

    methods: {
        isPayPalPosSalesChannel(salesChannelTypeId) {
            return salesChannelTypeId === PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
        },
    },
});

import type * as PayPal from 'src/types';
import type { RouteLocationRaw } from 'vue-router';
import template from './swag-paypal-disputes-detail.html.twig';
import './swag-paypal-disputes-detail.scss';

const { Filter, Utils } = Shopware;
const { Criteria } = Shopware.Data;
const { capitalizeString } = Shopware.Utils.string;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPayPalDisputeApiService',
        'systemConfigApiService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    props: {
        disputeId: {
            type: String,
            required: true,
        },

        salesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data(): {
        isLoading: boolean;
        dispute: null | PayPal.V1<'disputes_item'>;
        resolutionCenterUrl: string;
        orderModuleLink: null | RouteLocationRaw;
    } {
        return {
            isLoading: false,
            dispute: null,
            resolutionCenterUrl: 'https://www.paypal.com/resolutioncenter',
            orderModuleLink: null,
        };
    },

    computed: {
        orderTransactionRepository() {
            return this.repositoryFactory.create('order_transaction');
        },

        orderTransactionCriteria() {
            const custom = this.dispute?.disputed_transactions?.[0]?.custom;
            if (!custom) {
                return null;
            }

            // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment, @typescript-eslint/no-unsafe-member-access
            const id = JSON.parse(custom)?.orderTransactionId ?? custom;

            if (!(typeof id === 'string') || id.length !== 32) {
                return null;
            }

            const criteria = new Criteria(1, 1);
            criteria.setIds([id]);

            return criteria;
        },

        externalDetailPageLink() {
            return `${this.resolutionCenterUrl}/${this.dispute?.dispute_id ?? ''}`;
        },

        dateFilter() {
            return Filter.getByName('date');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.isLoading = true;

            const config = await this.systemConfigApiService.getValues('SwagPayPal.settings') as PayPal.SystemConfig;

            if (config['SwagPayPal.settings.sandbox']) {
                this.resolutionCenterUrl = 'https://www.sandbox.paypal.com/resolutioncenter';
            }

            this.getDetail();
        },

        getDetail() {
            this.SwagPayPalDisputeApiService.detail(this.disputeId, this.salesChannelId).then((dispute) => {
                this.dispute = dispute;
                this.setLinkToOrderModule();
                this.isLoading = false;
            }).catch(this.handleError.bind(this));
        },

        handleError(errorResponse: PayPal.ServiceError) {
            this.createNotificationFromError({ errorResponse, title: 'swag-paypal-disputes.list.errorTitle' });
            this.isLoading = false;
        },

        async setLinkToOrderModule() {
            if (!this.orderTransactionCriteria) {
                this.orderModuleLink = null;
            }

            const orderTransactions = await this.orderTransactionRepository.search(this.orderTransactionCriteria);
            const orderTransaction = orderTransactions[0];

            if (orderTransaction === null) {
                return;
            }

            this.orderModuleLink = { name: 'sw.order.detail.general', params: { id: orderTransaction.orderId } };
        },

        formatTechnicalText(technicalText: string): string {
            return capitalizeString(technicalText).replace(/_/g, ' ');
        },

        getInquiryClass(stage: string): string {
            if (stage === 'INQUIRY') {
                return 'swag-paypal-disputes-detail__stage-inquiry';
            }

            return 'swag-paypal-disputes-detail__stage-other';
        },

        getDueDate(sellerResponseDueDate: string, buyerResponseDueDate: string) {
            if (sellerResponseDueDate !== null) {
                return `${this.$tc('swag-paypal-disputes.common.response_due_date.seller')}: ${
                    this.formatDate(sellerResponseDueDate)}`;
            }

            if (buyerResponseDueDate !== null) {
                return `${this.$tc('swag-paypal-disputes.common.response_due_date.buyer')}: ${
                    this.formatDate(buyerResponseDueDate)}`;
            }

            return '';
        },

        copyToClipboard() {
            if (this.dispute === null) {
                return;
            }

            try {
                // eslint-disable-next-line @typescript-eslint/no-unsafe-call
                Utils.dom.copyToClipboard(JSON.stringify(this.dispute));
                this.createNotificationInfo({
                    message: this.$tc('global.sw-field.notification.notificationCopySuccessMessage'),
                });
            } catch (err) {
                this.createNotificationError({
                    message: this.$tc('global.sw-field.notification.notificationCopyFailureMessage'),
                });
            }
        },

        formatDate(dateTime: string) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },
    },
});

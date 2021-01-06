import template from './swag-paypal-disputes-list.html.twig';
import {
    DISPUTE_STATE_REQUIRED_ACTION,
    DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION,
    DISPUTE_STATE_UNDER_PAYPAL_REVIEW,
    DISPUTE_STATE_RESOLVED,
    DISPUTE_STATE_OPEN_INQUIRIES,
    DISPUTE_STATE_APPEALABLE
} from './swag-paypal-disputes-consts';
import './swag-paypal-disputes-list.scss';

const { Component, Filter } = Shopware;
const { debounce } = Shopware.Utils;

Component.register('swag-paypal-disputes-list', {
    template,

    inject: [
        'SwagPayPalDisputeApiService'
    ],

    mixins: ['notification'],

    data() {
        return {
            isLoading: false,
            disputes: [],
            disputeStates: [
                {
                    value: DISPUTE_STATE_REQUIRED_ACTION,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_REQUIRED_ACTION}`)
                },
                {
                    value: DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION}`)
                },
                {
                    value: DISPUTE_STATE_UNDER_PAYPAL_REVIEW,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_UNDER_PAYPAL_REVIEW}`)
                },
                {
                    value: DISPUTE_STATE_RESOLVED,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_RESOLVED}`)
                },
                {
                    value: DISPUTE_STATE_OPEN_INQUIRIES,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_OPEN_INQUIRIES}`)
                },
                {
                    value: DISPUTE_STATE_APPEALABLE,
                    label: this.$tc(`swag-paypal-disputes.list.disputeStates.${DISPUTE_STATE_APPEALABLE}`)
                }
            ],
            disputeStateFilter: [],
            salesChannelId: null,
            total: 0,
            limit: 10,
            page: 1
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        disputesColumns() {
            return [
                {
                    property: 'dispute_id',
                    label: this.$tc('swag-paypal-disputes.list.columns.dispute_id')
                },
                {
                    property: 'dispute_state',
                    label: this.$tc('swag-paypal-disputes.list.columns.dispute_state')
                },
                {
                    property: 'status',
                    label: this.$tc('swag-paypal-disputes.list.columns.status')
                },
                {
                    property: 'reason',
                    label: this.$tc('swag-paypal-disputes.list.columns.reason')
                },
                {
                    property: 'dispute_life_cycle_stage',
                    label: this.$tc('swag-paypal-disputes.list.columns.dispute_life_cycle_stage')
                },
                {
                    property: 'dispute_amount',
                    label: this.$tc('swag-paypal-disputes.list.columns.dispute_amount')
                },
                {
                    property: 'seller_response_due_date',
                    label: this.$tc('swag-paypal-disputes.list.columns.seller_response_due_date')
                },
                {
                    property: 'buyer_response_due_date',
                    label: this.$tc('swag-paypal-disputes.list.columns.buyer_response_due_date')
                },
                {
                    property: 'create_time',
                    label: this.$tc('swag-paypal-disputes.list.columns.create_time')
                },
                {
                    property: 'update_time',
                    label: this.$tc('swag-paypal-disputes.list.columns.update_time')
                }
            ];
        },

        visibleDisputes() {
            return this.disputes.slice((this.page - 1) * this.limit, (this.page - 1) * this.limit + this.limit);
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getList();
        },

        getList() {
            this.isLoading = true;
            let disputeStateFilter = null;
            if (this.disputeStateFilter.length > 0) {
                disputeStateFilter = this.disputeStateFilter.join(',');
            }

            this.SwagPayPalDisputeApiService.list(this.salesChannelId, disputeStateFilter).then((disputeList) => {
                this.disputes = [];
                if (disputeList.items !== null) {
                    this.disputes = disputeList.items;
                }
                this.total = this.disputes.length;
                this.isLoading = false;
            }).catch(this.handleError);
        },

        debouncedGetList: debounce(function updateList() {
            this.getList();
        }, 850),

        handleError(errorResponse) {
            const errorDetail = errorResponse.response.data.errors[0].detail;
            this.createNotificationError({
                message: `${this.$tc('swag-paypal-disputes.list.errorTitle')}: ${errorDetail}`,
                autoClose: false
            });
            this.isLoading = false;
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.$emit('page-change');
        },

        onRefresh() {
            this.getList();
        },

        onChangeDisputeStateFilter(value) {
            this.disputeStateFilter = value;
            this.debouncedGetList();
        },

        onSalesChannelChanged(value) {
            this.salesChannelId = value;
            this.getList();
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
});

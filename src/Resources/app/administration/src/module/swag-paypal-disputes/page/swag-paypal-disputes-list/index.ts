import type * as PayPal from 'src/types';
import template from './swag-paypal-disputes-list.html.twig';
import {
    DISPUTE_AUTH_ERROR,
    DISPUTE_STATE_APPEALABLE,
    DISPUTE_STATE_OPEN_INQUIRIES,
    DISPUTE_STATE_REQUIRED_ACTION,
    DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION,
    DISPUTE_STATE_RESOLVED,
    DISPUTE_STATE_UNDER_PAYPAL_REVIEW,
} from './swag-paypal-disputes-consts';
import './swag-paypal-disputes-list.scss';

const { Filter } = Shopware;
const { debounce } = Shopware.Utils;
const { capitalizeString } = Shopware.Utils.string;

export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'SwagPayPalDisputeApiService',
        'systemConfigApiService',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        isLoading: boolean;
        notAuthorized: boolean;
        disputes: PayPal.V1<'disputes_item'>[];
        disputeStates: { value: string; label: string }[];
        disputeStateFilter: string[];
        salesChannelId: null | string;
        total: number;
        limit: number;
        page: number;
        resolutionCenterUrl: string;
    } {
        return {
            isLoading: false,
            notAuthorized: false,
            disputes: [],
            disputeStates: [
                {
                    value: DISPUTE_STATE_REQUIRED_ACTION,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_REQUIRED_ACTION),
                },
                {
                    value: DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_REQUIRED_OTHER_PARTY_ACTION),
                },
                {
                    value: DISPUTE_STATE_UNDER_PAYPAL_REVIEW,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_UNDER_PAYPAL_REVIEW),
                },
                {
                    value: DISPUTE_STATE_RESOLVED,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_RESOLVED),
                },
                {
                    value: DISPUTE_STATE_OPEN_INQUIRIES,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_OPEN_INQUIRIES),
                },
                {
                    value: DISPUTE_STATE_APPEALABLE,
                    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
                    label: this.formatTechnicalText(DISPUTE_STATE_APPEALABLE),
                },
            ],
            disputeStateFilter: [],
            salesChannelId: null,
            total: 0,
            limit: 10,
            page: 1,
            resolutionCenterUrl: 'https://www.paypal.com/resolutioncenter',
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        showEmptyStateWithNoDisputes() {
            return !this.notAuthorized && this.disputes.length === 0;
        },

        disputesColumns() {
            return [
                {
                    property: 'dispute_id',
                    label: this.$tc('swag-paypal-disputes.common.dispute_id'),
                },
                {
                    property: 'update_time',
                    label: this.$tc('swag-paypal-disputes.common.update_time'),
                },
                {
                    property: 'response_due_date',
                    label: this.$tc('swag-paypal-disputes.common.response_due_date.label'),
                },
                {
                    property: 'status',
                    label: this.$tc('swag-paypal-disputes.common.status'),
                },
                {
                    property: 'dispute_life_cycle_stage',
                    label: this.$tc('swag-paypal-disputes.common.dispute_life_cycle_stage'),
                },
                {
                    property: 'dispute_amount',
                    label: this.$tc('swag-paypal-disputes.common.dispute_amount'),
                },
            ];
        },

        visibleDisputes() {
            return this.disputes.slice((this.page - 1) * this.limit, (this.page - 1) * this.limit + this.limit);
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

            this.getList();
        },

        getList() {
            this.isLoading = true;
            this.disputes = [];
            const disputeStateFilter = this.disputeStateFilter.join(',') || null;

            this.SwagPayPalDisputeApiService.list(this.salesChannelId, disputeStateFilter).then((disputeList) => {
                if (disputeList.items !== null) {
                    this.disputes = this.sortDisputes(disputeList.items);
                }
                this.total = this.disputes.length;
                this.isLoading = false;
            }).catch(this.handleError.bind(this));
        },

        sortDisputes(disputes: PayPal.V1<'disputes_item'>[]): PayPal.V1<'disputes_item'>[] {
            // sort resolved disputes as last
            return disputes.sort((a, b) => {
                if (a.status === 'RESOLVED') {
                    return 1;
                }

                if (b.status === 'RESOLVED') {
                    return -1;
                }

                return 0;
            });
        },

        debouncedGetList: debounce(function updateList() {
            // @ts-expect-error - this cannot corretly be typed
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access, @typescript-eslint/no-unsafe-call
            this.getList();
        }, 850),

        handleError(errorResponse: PayPal.ServiceError) {
            if (errorResponse.response?.data.errors?.[0]?.code === DISPUTE_AUTH_ERROR) {
                this.notAuthorized = true;
            } else {
                this.createNotificationFromError({ errorResponse, title: 'swag-paypal-disputes.list.errorTitle' });
            }

            this.isLoading = false;
        },

        formatUpdateDate(dateTime: string) {
            return this.formatDate(dateTime, {});
        },

        formatUpdateTime(dateTime: string) {
            return this.formatDate(dateTime, {
                day: undefined,
                month: undefined,
                year: undefined,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },

        formatTechnicalText(technicalText: string): string {
            return capitalizeString(technicalText).replace(/_/g, ' ');
        },

        onPageChange({ page, limit }: { page: number; limit: number }) {
            this.page = page;
            this.limit = limit;
            this.$emit('page-change');
        },

        onRefresh() {
            this.getList();
        },

        onChangeDisputeStateFilter(value: string[]) {
            this.disputeStateFilter = value;
            this.debouncedGetList();
        },

        onSalesChannelChanged(value: string | null) {
            this.salesChannelId = value;
            this.getList();
        },

        getExternalDetailPageLink(dispute: PayPal.V1<'disputes_item'>) {
            return `${this.resolutionCenterUrl}/${dispute.dispute_id ?? ''}`;
        },

        formatDate(dateTime: string, options: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' }) {
            return this.dateFilter(dateTime, options);
        },
    },
});

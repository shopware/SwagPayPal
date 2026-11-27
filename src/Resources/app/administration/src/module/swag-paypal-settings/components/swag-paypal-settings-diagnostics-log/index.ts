import type * as PayPal from 'SwagPayPal/types';
import { DATE_FORMAT } from 'SwagPayPal/constant/swag-paypal-diagnostics.constant';
import { LOG_LEVELS } from 'SwagPayPal/constant/swag-paypal-settings.constant';
import template from './swag-paypal-settings-diagnostics-log.html.twig';
import './swag-paypal-settings-diagnostics-log.scss';

const { Criteria } = Shopware.Data;

// the tones of the diagnostics rows' status badge
type LevelTone = 'critical' | 'attention' | 'brand' | 'secondary';

interface SelectOption<T> {
    value: T;
    label: string;
}

const LEVEL_NAMES: Record<number, string> = {
    100: 'debug',
    200: 'info',
    250: 'notice',
    300: 'warning',
    400: 'error',
    500: 'critical',
};

const PAGE_LIMIT = 25;

const PERIODS = {
    all: 0,
    hour: 60 * 60 * 1000,
    day: 24 * 60 * 60 * 1000,
    week: 7 * 24 * 60 * 60 * 1000,
} as const;

type Period = keyof typeof PERIODS;

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    data(): {
        lines: TEntityCollection<'swag_paypal_log'> | [];
        page: number;
        limit: number;
        total: number;
        isLoading: boolean;
        searchTerm: string;
        selectedLevels: number[];
        selectedCodes: string[];
        period: Period;
        codes: string[];
        detailLineId: string | null;
    } {
        return {
            lines: [],
            page: 1,
            limit: PAGE_LIMIT,
            total: 0,
            isLoading: false,
            searchTerm: '',
            selectedLevels: [],
            selectedCodes: [],
            period: 'all',
            codes: [],
            detailLineId: null,
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        logRepository(): TRepository<'swag_paypal_log'> {
            return this.repositoryFactory.create('swag_paypal_log');
        },

        salesChannelFilter() {
            if (!this.settingsStore.salesChannel) {
                return null;
            }

            // lines without a Sales Channel, like the ones of the API client, belong to every Sales Channel
            return Criteria.multi('OR', [
                Criteria.equals('salesChannelId', this.settingsStore.salesChannel),
                Criteria.equals('salesChannelId', null),
            ]);
        },

        levelOptions(): SelectOption<number>[] {
            return Object.keys(LEVEL_NAMES)
                .map((level) => ({ value: Number(level), label: this.levelLabel(Number(level)) }))
                .reverse();
        },

        periodOptions(): SelectOption<Period>[] {
            return (Object.keys(PERIODS) as Period[]).map((period) => ({
                value: period,
                label: this.$t(`swag-paypal-settings.diagnostics.log.filter.period.${period}`),
            }));
        },

        codeOptions(): SelectOption<string>[] {
            return this.codes.map((code) => ({ value: code, label: code }));
        },

        isFiltered(): boolean {
            return this.searchTerm.trim() !== '' || this.selectedLevels.length > 0
                || this.selectedCodes.length > 0 || this.period !== 'all';
        },

        logLevel(): number {
            // the default of `Settings::LOG_LEVEL`
            return this.settingsStore.getRoot('SwagPayPal.settings.logLevel') ?? LOG_LEVELS.warning;
        },

        logsOff(): boolean {
            return this.logLevel === LOG_LEVELS.off;
        },

        logLevelLabel(): string {
            const name = Object.entries(LOG_LEVELS).find(([, value]) => value === this.logLevel)?.[0];

            return name ? this.$t(`swag-paypal-settings.logs.level.${name}`) : String(this.logLevel);
        },

        emptyText(): string {
            if (this.isFiltered) {
                return this.$t('swag-paypal-settings.diagnostics.log.emptyFiltered');
            }

            return this.logsOff
                ? this.$t('swag-paypal-settings.diagnostics.log.emptyOff')
                : this.$t('swag-paypal-settings.diagnostics.log.empty');
        },

        columns() {
            const column = (property: string, label: string, width: string) => ({
                property,
                label: this.$t(`swag-paypal-settings.diagnostics.log.${label}`),
                sortable: false,
                width,
            });

            return [
                column('createdAt', 'columnTime', '170px'),
                column('level', 'columnLevel', '110px'),
                column('code', 'columnCode', '240px'),
                column('message', 'columnMessage', 'auto'),
            ];
        },

        detailLine(): TEntity<'swag_paypal_log'> | null {
            if (!this.detailLineId) {
                return null;
            }

            return this.lines.find((line) => line.id === this.detailLineId) ?? null;
        },
    },

    watch: {
        async 'settingsStore.salesChannel'() {
            if (!this.acl.can('swag_paypal_log:read')) {
                return;
            }

            this.page = 1;
            await this.loadFilterOptions();
            // a code without lines in this Sales Channel would empty the table without showing in the select
            this.selectedCodes = this.selectedCodes.filter((code) => this.codes.includes(code));
            await this.loadLines();
        },
    },

    created() {
        if (this.acl.can('swag_paypal_log:read')) {
            this.loadLines();
            this.loadFilterOptions();
        }
    },

    methods: {
        // not a computed, so every load measures the period from now
        createCriteria(): TCriteria {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));
            criteria.setTotalCountMode(1);

            const term = this.searchTerm.trim();
            if (term !== '') {
                criteria.addFilter(Criteria.multi('OR', [
                    Criteria.contains('message', term),
                    Criteria.contains('code', term),
                    Criteria.contains('context', term),
                    Criteria.contains('extra', term),
                ]));
            }

            const salesChannelFilter = this.salesChannelFilter;
            if (salesChannelFilter) {
                criteria.addFilter(salesChannelFilter);
            }

            if (this.selectedLevels.length > 0) {
                criteria.addFilter(Criteria.equalsAny('level', this.selectedLevels));
            }

            if (this.selectedCodes.length > 0) {
                criteria.addFilter(Criteria.equalsAny('code', this.selectedCodes));
            }

            if (this.period !== 'all') {
                criteria.addFilter(Criteria.range('createdAt', {
                    gte: new Date(Date.now() - PERIODS[this.period]).toISOString(),
                }));
            }

            return criteria;
        },

        async loadLines() {
            this.isLoading = true;

            try {
                const lines = await this.logRepository.search(this.createCriteria(), Shopware.Context.api);

                this.lines = lines;
                this.total = lines.total ?? 0;
            } catch (errorResponse) {
                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.log.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.log.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });
            } finally {
                this.isLoading = false;
            }
        },

        async loadFilterOptions() {
            const criteria = new Criteria(1, 1);
            criteria.addAggregation(Criteria.terms('codes', 'code'));
            criteria.addFilter(Criteria.not('AND', [Criteria.equals('code', null)]));

            const salesChannelFilter = this.salesChannelFilter;
            if (salesChannelFilter) {
                criteria.addFilter(salesChannelFilter);
            }

            try {
                const result = await this.logRepository.search(criteria, Shopware.Context.api);
                const buckets = (result.aggregations?.codes as { buckets?: { key: string | null }[] } | undefined)
                    ?.buckets ?? [];

                this.codes = buckets
                    .map((bucket) => bucket.key)
                    .filter((key): key is string => !!key)
                    .sort((a, b) => a.localeCompare(b));
            } catch (errorResponse) {
                this.createNotificationError({
                    title: this.$t('swag-paypal-settings.diagnostics.log.title'),
                    message: this.$t('swag-paypal-settings.diagnostics.log.errorMessage', {
                        message: this.createMessageFromError(errorResponse as PayPal.ServiceError),
                    }),
                });
            }
        },

        onPageChange({ page, limit }: { page: number; limit: number }) {
            this.page = page;
            this.limit = limit;

            return this.loadLines();
        },

        onSearch(term: string) {
            this.searchTerm = term;
            this.page = 1;

            return this.loadLines();
        },

        onLevelsChange(levels: number[]) {
            this.selectedLevels = levels;
            this.page = 1;

            return this.loadLines();
        },

        onCodesChange(codes: string[]) {
            this.selectedCodes = codes;
            this.page = 1;

            return this.loadLines();
        },

        onPeriodChange(period: Period) {
            this.period = period;
            this.page = 1;

            return this.loadLines();
        },

        onReload() {
            this.page = 1;

            return Promise.all([this.loadLines(), this.loadFilterOptions()]);
        },

        onOpenDetails(id: string) {
            this.detailLineId = id;
        },

        onCloseDetails() {
            this.detailLineId = null;
        },

        formatTime(createdAt: string): string {
            return Shopware.Filter.getByName('date')(createdAt, DATE_FORMAT);
        },

        formatJson(value?: Record<string, unknown> | null): string {
            return JSON.stringify(value ?? {}, null, 2);
        },

        levelName(level: number): string {
            return LEVEL_NAMES[level] ?? String(level);
        },

        levelLabel(level: number): string {
            const snippet = `swag-paypal-settings.logs.level.${this.levelName(level)}`;

            return this.$te(snippet) ? this.$t(snippet) : String(level);
        },

        levelTone(level: number): LevelTone {
            if (level >= LOG_LEVELS.error) {
                return 'critical';
            }

            if (level >= LOG_LEVELS.warning) {
                return 'attention';
            }

            return level >= LOG_LEVELS.info ? 'brand' : 'secondary';
        },
    },
});

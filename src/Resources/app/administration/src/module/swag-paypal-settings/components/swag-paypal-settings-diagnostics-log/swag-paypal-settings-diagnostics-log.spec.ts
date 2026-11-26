import { config, mount, type VueWrapper } from '@vue/test-utils';
import type { LOG_LEVEL } from 'SwagPayPal/constant/swag-paypal-settings.constant';
import SwagPayPalSettingsDiagnosticsLog from '.';

Shopware.Component.register('swag-paypal-settings-diagnostics-log', Promise.resolve(SwagPayPalSettingsDiagnosticsLog));

type Line = TEntity<'swag_paypal_log'>;
type Wrapper = Awaited<ReturnType<typeof createWrapper>>['wrapper'];

const SALES_CHANNEL_ID = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';
const LINE_ID = '98432def39fc4624b33213a56b8c944d';

// lines like the ones of the API client or the browser reports have no Sales Channel
const SALES_CHANNEL_FILTER = {
    type: 'multi',
    operator: 'OR',
    queries: [
        { type: 'equals', field: 'salesChannelId', value: SALES_CHANNEL_ID },
        { type: 'equals', field: 'salesChannelId', value: null },
    ],
};

const stubs = {
    'mt-card': { template: '<div class="mt-card"><slot name="subtitle" /><slot name="grid" /></div>' },
    'sw-container': { template: '<div class="sw-container"><slot /></div>' },
    'sw-card-filter': {
        props: ['placeholder'],
        emits: ['sw-card-filter-term-change'],
        template: '<input class="sw-card-filter" :placeholder="placeholder">',
    },
    'mt-select': {
        props: {
            modelValue: null,
            options: null,
            placeholder: null,
            enableMultiSelection: Boolean,
            small: Boolean,
        },
        emits: ['update:modelValue'],
        template: '<div class="mt-select" />',
    },
    'mt-button': { emits: ['click'], template: '<button class="mt-button" @click="$emit(\'click\')"><slot /></button>' },
    'mt-icon': true,
    'mt-link': { props: ['to', 'type'], template: '<a class="mt-link"><slot /></a>' },
    'sw-data-grid': {
        props: ['dataSource', 'columns', 'isLoading', 'showSelection', 'showActions'],
        template: `
            <div class="sw-data-grid">
                <div v-for="item in dataSource" :key="item.id" class="sw-data-grid__row">
                    <slot name="column-createdAt" :item="item" />
                    <slot name="column-level" :item="item" />
                    <slot name="column-message" :item="item" />
                </div>
            </div>`,
    },
    'sw-pagination': {
        props: ['page', 'limit', 'total', 'steps'],
        emits: ['page-change'],
        template: '<div class="sw-pagination" />',
    },
    'sw-modal': {
        props: ['title'],
        emits: ['modal-close'],
        template: '<div class="sw-modal"><slot /><slot name="modal-footer" /></div>',
    },
};

function createLine(line: Partial<Line> = {}): Line {
    return {
        id: LINE_ID,
        level: 400,
        code: 'CHECKOUT_PAYMENT_FAILED',
        message: 'Could not capture PayPal order 5O190127TN364715T',
        context: { orderId: '5O190127TN364715T' },
        extra: { uid: 'abc' },
        createdAt: '2026-11-20T14:30:15.000+00:00',
        ...line,
    } as Line;
}

function createLines(lines: Line[], total = lines.length) {
    return Object.assign([...lines], { total });
}

async function createWrapper({
    lines = createLines([createLine()]),
    privileges = ['swag_paypal_log:read'],
    codes = ['CHECKOUT_PAYMENT_FAILED', 'API_REQUEST_FAILED'],
}: {
    lines?: ReturnType<typeof createLines>;
    privileges?: string[];
    codes?: (string | null)[];
} = {}) {
    const logSearch = jest.fn((criteria: TCriteria) => {
        if (criteria.aggregations.length > 0) {
            return Promise.resolve({ aggregations: { codes: { buckets: codes.map((key) => ({ key, count: 1 })) } } });
        }

        return Promise.resolve(lines);
    });

    const wrapper = mount(
        await Shopware.Component.build('swag-paypal-settings-diagnostics-log') as typeof SwagPayPalSettingsDiagnosticsLog,
        {
            global: {
                stubs,
                provide: {
                    acl: { can: (privilege: string) => privileges.includes(privilege) },
                    repositoryFactory: {
                        create: jest.fn(() => ({ search: logSearch })),
                    },
                },
            },
        },
    );
    await flushPromises();

    return { wrapper, logSearch };
}

function vmOf(wrapper: Wrapper, selector: string) {
    return (wrapper.findComponent(selector) as VueWrapper).vm as unknown as {
        $props: Record<string, unknown>;
        $emit: (event: string, ...args: unknown[]) => void;
    };
}

async function emit(wrapper: Wrapper, selector: string, event: string, ...args: unknown[]) {
    vmOf(wrapper, selector).$emit(event, ...args);
    await flushPromises();
}

const search = (wrapper: Wrapper, term: string) => emit(wrapper, '.sw-card-filter', 'sw-card-filter-term-change', term);

const propsOf = (wrapper: Wrapper, selector: string) => vmOf(wrapper, selector).$props;

function select(wrapper: Wrapper, name: 'period' | 'level' | 'code') {
    return propsOf(wrapper, `.swag-paypal-settings-diagnostics-log__${name}-filter`);
}

const selectValues = (wrapper: Wrapper, name: 'period' | 'level' | 'code', values: unknown) => emit(
    wrapper,
    `.swag-paypal-settings-diagnostics-log__${name}-filter`,
    'update:modelValue',
    values,
);

async function changePage(wrapper: Wrapper, page: number, limit = 25) {
    await emit(wrapper, '.sw-pagination', 'page-change', { page, limit });
}

function lineCriteria(logSearch: jest.Mock): TCriteria[] {
    return logSearch.mock.calls.map(([criteria]) => criteria as TCriteria).filter((c) => c.aggregations.length === 0);
}

function codeCriteria(logSearch: jest.Mock): TCriteria[] {
    return logSearch.mock.calls.map(([criteria]) => criteria as TCriteria).filter((c) => c.aggregations.length > 0);
}

const lastLineCriteria = (logSearch: jest.Mock) => lineCriteria(logSearch).at(-1) as TCriteria;
const lastCodeCriteria = (logSearch: jest.Mock) => codeCriteria(logSearch).at(-1) as TCriteria;

describe('swag-paypal-settings-diagnostics-log', () => {
    beforeAll(() => {
        // i18n-t renders the link only for a message with its placeholders
        const i18n = config.global.plugins.find((plugin) => 'global' in plugin) as unknown as {
            global: { mergeLocaleMessage: (locale: string, messages: Record<string, string>) => void };
        };
        i18n.global.mergeLocaleMessage('en', {
            'swag-paypal-settings.diagnostics.log.subtitle': 'Level {level}, change it in the {advanced} tab.',
            'swag-paypal-settings.tabs.advanced': 'Advanced',
        });
    });

    afterEach(() => {
        Shopware.Store.get('swagPayPalSettings').$reset();
    });

    describe('lines', () => {
        it('should load the newest lines first', async () => {
            const { logSearch } = await createWrapper();

            const criteria = lastLineCriteria(logSearch);
            expect(criteria.page).toBe(1);
            expect(criteria.limit).toBe(25);
            expect(criteria.sortings).toEqual([{ field: 'createdAt', order: 'DESC', naturalSorting: false }]);
            expect(criteria.totalCountMode).toBe(1);
            expect(criteria.filters).toEqual([]);
        });

        it('should load the lines of the selected Sales Channel and the ones without a Sales Channel', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;

            const { logSearch } = await createWrapper();

            expect(lastLineCriteria(logSearch).filters).toEqual([SALES_CHANNEL_FILTER]);
        });

        it('should reload the first page when the Sales Channel changes', async () => {
            const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 60) });
            await changePage(wrapper, 2);
            const before = lineCriteria(logSearch).length;

            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
            await flushPromises();

            expect(lineCriteria(logSearch)).toHaveLength(before + 1);
            expect(lastLineCriteria(logSearch).page).toBe(1);
            expect(lastLineCriteria(logSearch).filters).toEqual([SALES_CHANNEL_FILTER]);

            Shopware.Store.get('swagPayPalSettings').salesChannel = null;
            await flushPromises();
            expect(lastLineCriteria(logSearch).filters).toEqual([]);
        });

        it('should search message, code, context and extra and go back to the first page', async () => {
            const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 60) });
            await changePage(wrapper, 2);
            expect(lastLineCriteria(logSearch).page).toBe(2);

            expect(wrapper.find('.sw-card-filter').attributes('placeholder'))
                .toBe('swag-paypal-settings.diagnostics.log.searchPlaceholder');
            await search(wrapper, ' 5O190127TN364715T ');

            const criteria = lastLineCriteria(logSearch);
            expect(criteria.page).toBe(1);
            expect(criteria.filters).toEqual([{
                type: 'multi',
                operator: 'OR',
                queries: ['message', 'code', 'context', 'extra'].map((field) => ({
                    type: 'contains',
                    field,
                    value: '5O190127TN364715T',
                })),
            }]);
        });

        it.each([
            ['level', [400, 500], 'level', '400|500'],
            ['code', ['API_REQUEST_FAILED'], 'code', 'API_REQUEST_FAILED'],
        ] as const)('should filter by the %s and go back to the first page', async (name, values, field, value) => {
            const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 60) });
            await changePage(wrapper, 2);

            await selectValues(wrapper, name, [...values]);

            const criteria = lastLineCriteria(logSearch);
            expect(criteria.page).toBe(1);
            expect(criteria.filters).toEqual([{ type: 'equalsAny', field, value }]);

            await selectValues(wrapper, name, []);
            expect(lastLineCriteria(logSearch).filters).toEqual([]);
        });

        it('should combine the Sales Channel, the search, the level and the code', async () => {
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
            const { wrapper, logSearch } = await createWrapper();

            await search(wrapper, 'abc');
            await selectValues(wrapper, 'level', [400]);
            await selectValues(wrapper, 'code', ['API_REQUEST_FAILED']);

            expect(lastLineCriteria(logSearch).filters.map((filter) => filter.type))
                .toEqual(['multi', 'multi', 'equalsAny', 'equalsAny']);
        });

        describe('period', () => {
            const NOW = new Date('2026-11-27T12:00:00.000Z');

            beforeEach(() => {
                jest.useFakeTimers({ doNotFake: ['nextTick', 'setImmediate', 'setTimeout', 'queueMicrotask'] });
                jest.setSystemTime(NOW);
            });

            afterEach(() => {
                jest.useRealTimers();
            });

            it('should add no range for all time', async () => {
                const { wrapper, logSearch } = await createWrapper();

                expect(select(wrapper, 'period').modelValue).toBe('all');
                expect(lastLineCriteria(logSearch).filters).toEqual([]);
            });

            it.each([
                ['hour', '2026-11-27T11:00:00.000Z'],
                ['day', '2026-11-26T12:00:00.000Z'],
                ['week', '2026-11-20T12:00:00.000Z'],
            ])('should load the lines of the last %s and go back to the first page', async (period, from) => {
                const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 60) });
                await changePage(wrapper, 2);

                await selectValues(wrapper, 'period', period);

                const criteria = lastLineCriteria(logSearch);
                expect(criteria.page).toBe(1);
                expect(criteria.filters).toEqual([{ type: 'range', field: 'createdAt', parameters: { gte: from } }]);
            });

            it('should move the window on reload and not narrow the code options', async () => {
                const { wrapper, logSearch } = await createWrapper();
                await selectValues(wrapper, 'period', 'hour');

                jest.setSystemTime(new Date('2026-11-27T12:30:00.000Z'));
                await wrapper.find('.swag-paypal-settings-diagnostics-log__reload').trigger('click');
                await flushPromises();

                expect(lastLineCriteria(logSearch).filters).toEqual([
                    { type: 'range', field: 'createdAt', parameters: { gte: '2026-11-27T11:30:00.000Z' } },
                ]);
                expect(lastCodeCriteria(logSearch).filters).toEqual([
                    { type: 'not', operator: 'AND', queries: [{ type: 'equals', field: 'code', value: null }] },
                ]);
            });

            it('should offer the periods and count as a filter', async () => {
                const { wrapper } = await createWrapper({ lines: createLines([]) });

                expect((select(wrapper, 'period').options as { value: string }[]).map((option) => option.value))
                    .toEqual(['all', 'hour', 'day', 'week']);

                await selectValues(wrapper, 'period', 'day');
                expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').text())
                    .toBe('swag-paypal-settings.diagnostics.log.emptyFiltered');
            });
        });

        it('should offer the levels from critical to debug', async () => {
            const { wrapper } = await createWrapper();

            const options = select(wrapper, 'level').options as { value: number }[];
            expect(options.map((option) => option.value)).toEqual([500, 400, 300, 250, 200, 100]);
            expect(select(wrapper, 'level').placeholder).toBe('swag-paypal-settings.diagnostics.log.filter.level');
            expect(select(wrapper, 'level').enableMultiSelection).toBe(true);
        });

        it('should load the page and the page size from the pagination', async () => {
            const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 120) });

            expect(propsOf(wrapper, '.sw-pagination')).toEqual(expect.objectContaining({
                page: 1,
                limit: 25,
                total: 120,
                steps: [25, 50, 100],
            }));

            await changePage(wrapper, 3, 50);

            expect(lastLineCriteria(logSearch).page).toBe(3);
            expect(lastLineCriteria(logSearch).limit).toBe(50);
        });

        it('should show a notification when loading fails', async () => {
            const { wrapper } = await createWrapper();
            wrapper.vm.createNotificationError = jest.fn();
            wrapper.vm.logRepository.search = jest.fn(() => Promise.reject(new Error('failed')));

            await wrapper.vm.loadLines();

            expect(wrapper.vm.createNotificationError).toHaveBeenCalledWith(expect.objectContaining({
                title: 'swag-paypal-settings.diagnostics.log.title',
            }));
            expect(wrapper.vm.isLoading).toBe(false);
        });
    });

    describe('code options', () => {
        it('should aggregate the codes of all lines', async () => {
            const { wrapper, logSearch } = await createWrapper();

            expect(lastCodeCriteria(logSearch).aggregations[0])
                .toEqual(expect.objectContaining({ type: 'terms', name: 'codes', field: 'code' }));
            expect((select(wrapper, 'code').options as { value: string }[]).map((option) => option.value))
                .toEqual(['API_REQUEST_FAILED', 'CHECKOUT_PAYMENT_FAILED']);
        });

        it('should leave out lines without a code', async () => {
            const { wrapper } = await createWrapper({ codes: [null, 'API_REQUEST_FAILED'] });

            expect((select(wrapper, 'code').options as { value: string }[]).map((option) => option.value))
                .toEqual(['API_REQUEST_FAILED']);
        });

        it('should not narrow them by the search or the level', async () => {
            const { wrapper, logSearch } = await createWrapper();
            const before = codeCriteria(logSearch).length;

            await search(wrapper, 'abc');
            await selectValues(wrapper, 'level', [400]);

            expect(codeCriteria(logSearch)).toHaveLength(before);
            expect(lastCodeCriteria(logSearch).filters).toEqual([
                { type: 'not', operator: 'AND', queries: [{ type: 'equals', field: 'code', value: null }] },
            ]);
        });

        it('should be scoped to the selected Sales Channel and reloaded when it changes', async () => {
            const { logSearch } = await createWrapper();
            expect(codeCriteria(logSearch)).toHaveLength(1);

            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
            await flushPromises();

            expect(codeCriteria(logSearch)).toHaveLength(2);
            expect(lastCodeCriteria(logSearch).filters).toContainEqual(SALES_CHANNEL_FILTER);
        });

        it('should drop selected codes without lines in the new Sales Channel', async () => {
            const codes = ['API_REQUEST_FAILED', 'CHECKOUT_PAYMENT_FAILED'];
            const { wrapper, logSearch } = await createWrapper({ codes });
            await selectValues(wrapper, 'code', ['API_REQUEST_FAILED', 'CHECKOUT_PAYMENT_FAILED']);

            codes.splice(0, 1);
            Shopware.Store.get('swagPayPalSettings').salesChannel = SALES_CHANNEL_ID;
            await flushPromises();

            expect(select(wrapper, 'code').modelValue).toEqual(['CHECKOUT_PAYMENT_FAILED']);
            expect(lastLineCriteria(logSearch).filters)
                .toContainEqual({ type: 'equalsAny', field: 'code', value: 'CHECKOUT_PAYMENT_FAILED' });
        });
    });

    describe('reload', () => {
        it('should reload the lines and the code options on the first page and keep the filters', async () => {
            const { wrapper, logSearch } = await createWrapper({ lines: createLines([createLine()], 60) });
            await search(wrapper, 'abc');
            await changePage(wrapper, 2);
            const lines = lineCriteria(logSearch).length;
            const codes = codeCriteria(logSearch).length;

            await wrapper.find('.swag-paypal-settings-diagnostics-log__reload').trigger('click');
            await flushPromises();

            expect(lineCriteria(logSearch)).toHaveLength(lines + 1);
            expect(codeCriteria(logSearch)).toHaveLength(codes + 1);
            expect(lastLineCriteria(logSearch).page).toBe(1);
            expect(lastLineCriteria(logSearch).filters).toHaveLength(1);
        });

        it('should have an accessible label', async () => {
            const { wrapper } = await createWrapper();

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__reload').attributes('aria-label'))
                .toBe('swag-paypal-settings.diagnostics.log.reload');
        });
    });

    describe('subtitle', () => {
        it.each([
            [undefined, 'swag-paypal-settings.logs.level.warning'],
            [100, 'swag-paypal-settings.logs.level.debug'],
            [0, 'swag-paypal-settings.logs.level.off'],
        ])('should name the stored log level %s', async (logLevel, levelLabel) => {
            if (logLevel !== undefined) {
                Shopware.Store.get('swagPayPalSettings').setConfig(null, { 'SwagPayPal.settings.logLevel': logLevel as LOG_LEVEL });
            }

            const { wrapper } = await createWrapper();

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__subtitle').text())
                .toBe(`Level ${levelLabel}, change it in the swag-paypal-settings.tabs.advanced tab.`);
        });

        it('should link to the log level setting in the Advanced tab', async () => {
            const { wrapper } = await createWrapper();

            const link = wrapper.find('.swag-paypal-settings-diagnostics-log__subtitle .mt-link');
            const linkProps = propsOf(wrapper, '.swag-paypal-settings-diagnostics-log__subtitle .mt-link');
            expect(linkProps.type).toBe('internal');
            expect(linkProps.to).toEqual({ name: 'swag.paypal.settings.index.advanced' });
            expect(link.text()).toBe('swag-paypal-settings.tabs.advanced');
        });
    });

    describe('grid', () => {
        it('should show the lines without selection and actions', async () => {
            const { wrapper } = await createWrapper();

            const grid = propsOf(wrapper, '.sw-data-grid');
            expect(grid.showSelection).toBe(false);
            expect(grid.showActions).toBe(false);
            expect((grid.columns as { property: string; sortable: boolean }[])
                .map(({ property, sortable }) => [property, sortable]))
                .toEqual([['createdAt', false], ['level', false], ['code', false], ['message', false]]);
            expect(grid.dataSource).toHaveLength(1);
        });

        it('should show the full time and the level as a badge', async () => {
            const { wrapper } = await createWrapper();

            expect(wrapper.find('.sw-data-grid__row').text()).toContain(wrapper.vm.formatTime('2026-11-20T14:30:15.000+00:00'));
            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__level').classes())
                .toContain('swag-paypal-settings-diagnostics-log__level--critical');
            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__level').text())
                .toBe('swag-paypal-settings.logs.level.error');
        });

        it.each([
            [100, 'secondary'],
            [200, 'brand'],
            [250, 'brand'],
            [300, 'attention'],
            [400, 'critical'],
            [500, 'critical'],
        ])('should show level %i with the %s tone', async (level, variant) => {
            const { wrapper } = await createWrapper({ lines: createLines([createLine({ level })]) });

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__level').classes())
                .toContain(`swag-paypal-settings-diagnostics-log__level--${variant}`);
        });
    });

    describe('empty state', () => {
        it('should show an empty text instead of the grid', async () => {
            const { wrapper } = await createWrapper({ lines: createLines([]) });

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').text())
                .toBe('swag-paypal-settings.diagnostics.log.empty');
            expect(wrapper.find('.sw-data-grid').exists()).toBe(false);
        });

        it('should say that no logs are stored when the level is off', async () => {
            Shopware.Store.get('swagPayPalSettings').setConfig(null, { 'SwagPayPal.settings.logLevel': 0 });

            const { wrapper } = await createWrapper({ lines: createLines([]) });

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').text())
                .toBe('swag-paypal-settings.diagnostics.log.emptyOff');
        });

        it('should say that no line matches while searching', async () => {
            Shopware.Store.get('swagPayPalSettings').setConfig(null, { 'SwagPayPal.settings.logLevel': 0 });

            const { wrapper } = await createWrapper({ lines: createLines([]) });
            await search(wrapper, 'abc');

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').text())
                .toBe('swag-paypal-settings.diagnostics.log.emptyFiltered');
        });

        it.each(['level', 'code'] as const)('should say that no line matches while filtering by the %s', async (name) => {
            const { wrapper } = await createWrapper({ lines: createLines([]) });

            await selectValues(wrapper, name, name === 'level' ? [400] : ['API_REQUEST_FAILED']);

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').text())
                .toBe('swag-paypal-settings.diagnostics.log.emptyFiltered');
        });

        it('should keep the grid while loading', async () => {
            const { wrapper } = await createWrapper({ lines: createLines([]) });
            await wrapper.setData({ isLoading: true });

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log__empty').exists()).toBe(false);
            expect(wrapper.find('.sw-data-grid').exists()).toBe(true);
        });
    });

    describe('details', () => {
        const openDetails = async (wrapper: Wrapper) => {
            await wrapper.find('.swag-paypal-settings-diagnostics-log__message').trigger('click');
            await flushPromises();
        };

        it('should open a line from its message with the message, context and extra', async () => {
            const { wrapper } = await createWrapper({
                lines: createLines([createLine({ context: { orderId: 'abc', nested: { a: 1 } }, extra: { uid: 'xyz' } })]),
            });
            expect(wrapper.find('.sw-modal').exists()).toBe(false);

            await openDetails(wrapper);

            const modal = wrapper.find('.swag-paypal-settings-diagnostics-log-detail');
            expect(modal.exists()).toBe(true);
            expect(modal.find('.swag-paypal-settings-diagnostics-log-detail__message').text())
                .toBe('Could not capture PayPal order 5O190127TN364715T');
            expect(modal.find('.swag-paypal-settings-diagnostics-log-detail__code').text()).toBe('CHECKOUT_PAYMENT_FAILED');
            expect(modal.find('.swag-paypal-settings-diagnostics-log-detail__level').classes()).toContain('swag-paypal-settings-diagnostics-log__level--critical');
            expect(modal.find('.swag-paypal-settings-diagnostics-log-detail__context').text())
                .toBe(JSON.stringify({ orderId: 'abc', nested: { a: 1 } }, null, 2));
            expect(modal.find('.swag-paypal-settings-diagnostics-log-detail__extra').text())
                .toBe(JSON.stringify({ uid: 'xyz' }, null, 2));
        });

        it('should show an empty value as an empty object', async () => {
            const { wrapper } = await createWrapper({ lines: createLines([createLine({ context: null, extra: undefined })]) });

            await openDetails(wrapper);

            expect(wrapper.find('.swag-paypal-settings-diagnostics-log-detail__context').text()).toBe('{}');
            expect(wrapper.find('.swag-paypal-settings-diagnostics-log-detail__extra').text()).toBe('{}');
        });

        it('should close the line with the button and with the modal', async () => {
            const { wrapper } = await createWrapper();

            await openDetails(wrapper);
            await wrapper.find('.sw-modal .mt-button').trigger('click');
            expect(wrapper.find('.sw-modal').exists()).toBe(false);

            await openDetails(wrapper);
            await emit(wrapper, '.sw-modal', 'modal-close');
            expect(wrapper.find('.sw-modal').exists()).toBe(false);
        });
    });

    it('should be hidden and not load without the privilege', async () => {
        const { wrapper, logSearch } = await createWrapper({ privileges: [] });

        expect(logSearch).not.toHaveBeenCalled();
        expect(wrapper.find('.mt-card').exists()).toBe(false);
    });
});

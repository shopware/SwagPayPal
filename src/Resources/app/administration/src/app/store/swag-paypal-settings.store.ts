import type * as PayPal from 'SwagPayPal/types';

type State = {
    salesChannel: string | null;
    allConfigs: Readonly<Record<string, PayPal.SystemConfig>>;
};

const store = Shopware.Store.register({
    id: 'swagPayPalSettings',

    state: (): State => ({
        salesChannel: null,
        allConfigs: {},
    }),

    actions: {
        setConfig(salesChannelId: string | null, config: PayPal.SystemConfig) {
            // @ts-expect-error - we are allowed to mutate the state
            this.allConfigs[String(salesChannelId)] = config;
        },

        hasConfig(salesChannelId: string | null): boolean {
            return this.allConfigs.hasOwnProperty(String(salesChannelId));
        },

        set<K extends keyof PayPal.SystemConfig>(key: K, value: PayPal.SystemConfig[K]) {
            this.allConfigs[String(this.salesChannel)][key] = value;
        },

        get<K extends keyof PayPal.SystemConfig>(key: K): PayPal.SystemConfig[K] {
            return this.actual[key] ?? this.root[key];
        },

        getRoot<K extends keyof PayPal.SystemConfig>(key: K): PayPal.SystemConfig[K] {
            return this.root[key];
        },

        getActual<K extends keyof PayPal.SystemConfig>(key: K): PayPal.SystemConfig[K] {
            return this.actual[key];
        },
    },

    getters: {
        isLoading(): boolean {
            return !this.allConfigs.hasOwnProperty(String(this.salesChannel));
        },

        isSandbox(): boolean {
            return this.actual['SwagPayPal.settings.sandbox'] ?? this.root['SwagPayPal.settings.sandbox'] ?? false;
        },

        root(): PayPal.SystemConfig {
            return this.allConfigs.null ?? {};
        },

        actual(): PayPal.SystemConfig {
            return this.allConfigs[String(this.salesChannel)] ?? {};
        },
    },
});

export type SettingsStore = ReturnType<typeof store>;
export default store;

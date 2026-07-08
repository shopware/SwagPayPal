import template from './swag-paypal-settings-banner-preview.html.twig';
import './swag-paypal-settings-banner-preview.scss';

const { debounce } = Shopware.Utils;

const SCRIPT_ATTR_CORE = 'data-swag-paypal-banner-preview-core';

const LOGO_TYPE_MAP: Record<string, string> = {
    primary: 'WORDMARK',
    alternative: 'MONOGRAM',
    inline: 'TEXT',
    none: 'TEXT',
};

// SDK v6 constraint: logoType dictates the required logoPosition
const LOGO_POSITION_MAP: Record<string, string> = {
    MONOGRAM: 'LEFT',
    TEXT: 'INLINE',
    WORDMARK: 'LEFT',
};

// Converts stored system-config values (v5 lowercase) to SDK v6 uppercase.
// 'grayscale' is kept as the stored value for v5 storefront compatibility and remapped
// to 'MONOCHROME' only here in the preview; the v6 branch handles the same remapping for production.
const TEXT_COLOR_MAP: Record<string, string> = {
    black: 'BLACK',
    white: 'WHITE',
    monochrome: 'MONOCHROME',
    grayscale: 'MONOCHROME',
};

type MessagesInstance = {
    fetchContent: (opts: {
        amount: string;
        currencyCode: string;
        buyerCountry?: string;
        logoType?: string;
        logoPosition?: string;
        textColor?: string;
        onReady: (content: unknown) => void;
    }) => Promise<void>;
};

type SdkInstance = {
    createPayPalMessages: (opts: { currencyCode: string }) => MessagesInstance;
};

type PayPalV6 = {
    createInstance: (opts: { clientId: string; merchantId?: string; components: string[] }) => Promise<SdkInstance>;
};

type PaypalMessageElement = HTMLElement & { setContent: (content: unknown) => void };

let sdkInitialized = false;
let messagesInstance: MessagesInstance | null = null;
let refreshSeq = 0;

export default Shopware.Component.wrapComponentConfig({
    template,

    data() {
        return {
            sdkError: false,
            noOffersAvailable: false,
        };
    },

    computed: {
        store() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        clientId(): string {
            const isSandbox = !!this.store.getActual('SwagPayPal.settings.sandbox');
            const key = isSandbox
                ? 'SwagPayPal.settings.clientIdSandbox'
                : 'SwagPayPal.settings.clientId';
            return this.store.getActual(key) ?? '';
        },

        logoType(): string {
            const raw = this.store.getActual('SwagPayPal.settings.installmentBannerLogoType') ?? 'primary';
            return LOGO_TYPE_MAP[raw] ?? 'WORDMARK';
        },

        textColor(): string {
            const raw = this.store.getActual('SwagPayPal.settings.installmentBannerTextColor') ?? 'monochrome';
            return TEXT_COLOR_MAP[raw] ?? 'MONOCHROME';
        },

        textSize(): number {
            const raw = this.store.getActual('SwagPayPal.settings.installmentBannerTextSize') ?? '12';
            return Number(raw);
        },

        isSandbox(): boolean {
            return !!this.store.getActual('SwagPayPal.settings.sandbox');
        },

        merchantId(): string {
            const key = this.isSandbox
                ? 'SwagPayPal.settings.merchantPayerIdSandbox'
                : 'SwagPayPal.settings.merchantPayerId';
            return this.store.getActual(key) ?? '';
        },
    },

    watch: {
        logoType() {
            this.debouncedRefreshPreview();
        },
        textColor() {
            this.debouncedRefreshPreview();
        },
        textSize() {
            this.debouncedRefreshPreview();
        },
        clientId: {
            immediate: true,
            handler() {
                this.loadSdk();
            },
        },
    },

    methods: {
        debouncedRefreshPreview: debounce(function refreshPreview() {
            // @ts-expect-error - this cannot correctly be typed in debounce context
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call, @typescript-eslint/no-unsafe-member-access
            void this.refreshPreview();
        }, 300),

        loadSdk() {
            if (!this.clientId) {
                sdkInitialized = false;
                messagesInstance = null;
                this.sdkError = false;
                this.noOffersAvailable = false;
                document.querySelector(`script[${SCRIPT_ATTR_CORE}]`)?.remove();
                return;
            }

            this.sdkError = false;
            this.noOffersAvailable = false;

            const baseUrl = this.isSandbox
                ? 'https://www.sandbox.paypal.com/web-sdk/v6'
                : 'https://www.paypal.com/web-sdk/v6';

            const existing = document.querySelector(`script[${SCRIPT_ATTR_CORE}]`);
            if (existing) {
                void this.initSdk();
                return;
            }

            const script = document.createElement('script');
            script.src = `${baseUrl}/core`;
            script.setAttribute(SCRIPT_ATTR_CORE, '');
            script.onload = () => { void this.initSdk(); };
            document.head.appendChild(script);
        },

        async initSdk() {
            if (sdkInitialized) {
                await this.refreshPreview();
                return;
            }

            const paypal = (window as unknown as { paypal?: PayPalV6 }).paypal;
            if (!paypal?.createInstance) return;

            sdkInitialized = true;

            try {
                const sdkInstance = await paypal.createInstance({
                    clientId: this.clientId,
                    ...(this.merchantId ? { merchantId: this.merchantId } : {}),
                    components: ['paypal-messages'],
                });

                messagesInstance = sdkInstance.createPayPalMessages({ currencyCode: 'USD' });
                await this.refreshPreview();
            } catch {
                sdkInitialized = false;
                this.sdkError = true;
            }
        },

        async refreshPreview() {
            if (!messagesInstance) return;

            await this.$nextTick();

            // In production $refs returns the native element; in test stubs it's a component exposing the DOM node via $el.
            const ref = this.$refs.paypalMessage as (PaypalMessageElement & { $el?: PaypalMessageElement }) | null;
            const el = ref?.$el ?? ref;
            if (!el) return;

            el.style.setProperty('--paypal-message-font-size', `${this.textSize}px`);

            const seq = ++refreshSeq;
            const logoType = this.logoType;
            const logoPosition = LOGO_POSITION_MAP[logoType] ?? 'LEFT';

            try {
                await messagesInstance.fetchContent({
                    amount: '200',
                    currencyCode: 'USD',
                    buyerCountry: 'US',
                    logoType,
                    logoPosition,
                    textColor: this.textColor,
                    onReady: (content) => {
                        if (seq === refreshSeq) {
                            this.noOffersAvailable = false;
                            el.setContent(content);
                        }
                    },
                });
            } catch {
                this.noOffersAvailable = true;
            }
        },
    },
});

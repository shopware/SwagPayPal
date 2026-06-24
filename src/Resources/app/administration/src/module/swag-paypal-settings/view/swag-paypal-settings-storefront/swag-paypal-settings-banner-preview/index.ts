import template from './swag-paypal-settings-banner-preview.html.twig';
import './swag-paypal-settings-banner-preview.scss';

const PREVIEW_SCRIPT_ATTR = 'data-swag-paypal-banner-preview';

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        clientId(): string {
            const store = Shopware.Store.get('swagPayPalSettings');
            const isSandbox = !!store.getActual('SwagPayPal.settings.sandbox');
            const key = isSandbox
                ? 'SwagPayPal.settings.clientIdSandbox'
                : 'SwagPayPal.settings.clientId';
            return (store.getActual(key) ?? '') as string;
        },

        logoType(): string {
            return (Shopware.Store.get('swagPayPalSettings').getActual('SwagPayPal.settings.installmentBannerLogoType') ?? 'primary') as string;
        },

        textColor(): string {
            return (Shopware.Store.get('swagPayPalSettings').getActual('SwagPayPal.settings.installmentBannerTextColor') ?? 'monochrome') as string;
        },

        textSize(): number {
            const raw = Shopware.Store.get('swagPayPalSettings').getActual('SwagPayPal.settings.installmentBannerTextSize') ?? '12';
            return Number(raw);
        },
    },

    mounted() {
        this.$watch(
            () => this.logoType,
            () => this.$nextTick(() => this.renderPreview()),
        );
        this.$watch(
            () => this.textColor,
            () => this.$nextTick(() => this.renderPreview()),
        );
        this.$watch(
            () => this.textSize,
            () => this.$nextTick(() => this.renderPreview()),
        );
        this.$watch(
            () => this.clientId,
            () => this.loadSdk(),
            { immediate: true },
        );
    },

    methods: {
        loadSdk() {
            const clientId = this.clientId || 'test';
            const sdkUrl = `https://www.paypal.com/sdk/js?components=messages&client-id=${clientId}`;
            const existing = document.querySelector(`script[${PREVIEW_SCRIPT_ATTR}]`);

            if (existing) {
                if (existing.getAttribute('src') === sdkUrl) {
                    this.$nextTick(() => this.renderPreview());
                    return;
                }
                existing.remove();
            }

            const script = document.createElement('script');
            script.src = sdkUrl;
            script.setAttribute(PREVIEW_SCRIPT_ATTR, '');
            script.onload = () => this.renderPreview();
            document.head.appendChild(script);
        },

        renderPreview() {
            const container = this.$refs.previewContainer as HTMLElement | undefined;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const paypal = (window as any).paypal;
            if (!container || !paypal?.Messages) {
                return;
            }

            container.innerHTML = '';
            const el = document.createElement('div');
            container.appendChild(el);

            paypal.Messages({
                amount: 200,
                style: {
                    layout: 'text',
                    logo: { type: this.logoType },
                    text: { color: this.textColor, size: this.textSize },
                },
            }).render(el);
        },
    },
});

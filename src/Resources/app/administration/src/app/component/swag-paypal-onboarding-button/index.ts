import template from './swag-paypal-onboarding-button.html.twig';
import './swag-paypal-onboarding-button.scss';
import type * as PayPal from 'SwagPayPal/types';

/**
 * @private - The component has a stable public API (props), but expect that implementation details may change.
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    inject: [
        'acl',
        'SwagPayPalSettingsService',
    ],

    emits: ['onboarded'],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-notification'),
    ],

    props: {
        mode: {
            type: String as PropType<'live' | 'sandbox'>,
            required: false,
            default: 'live',
        },
        variant: {
            type: String as PropType<'ghost' | 'link'>,
            required: false,
            default: 'ghost',
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            // Will allow local overrides as props are readonly.
            // Note that this is overridden if the prop changes.
            type: this.mode,

            callbackId: Shopware.Utils.createId(),

            isLoading: true,

            scriptId: 'paypal-js',
            scriptURL: 'https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js',

            live: {
                partnerId: 'DYKPBPEAW5JNA',
                partnerClientId: 'AR1aQ13lHxH1c6b3CDd8wSY6SWad2Lt5fv5WkNIZg-qChBoGNfHr2kT180otUmvE_xXtwkgahXUBBurW',
                sellerNonce: `${Shopware.Utils.createId()}${Shopware.Utils.createId()}`,
            },
            sandbox: {
                partnerId: '45KXQA7PULGAG',
                partnerClientId: 'AQ9g8qMYHpE8s028VCq_GO3Roy9pjeqGDjKTkR_sxzX0FtncBb3QUWbFtoQMtdpe2lG9NpnDT419dK8s',
                sellerNonce: `${Shopware.Utils.createId()}${Shopware.Utils.createId()}`,
            },
            commonRequestParams: {
                channelId: 'partner',
                product: 'ppcp',
                secondaryProducts: 'advanced_vaulting,PAYMENT_METHODS',
                capabilities: [
                    'APPLE_PAY',
                    'GOOGLE_PAY',
                    'PAY_UPON_INVOICE',
                    'PAYPAL_WALLET_VAULTING_ADVANCED',
                ].join(','),
                integrationType: 'FO',
                features: [
                    'PAYMENT',
                    'REFUND',
                    'READ_SELLER_DISPUTE',
                    'UPDATE_SELLER_DISPUTE',
                    'ADVANCED_TRANSACTIONS_SEARCH',
                    'ACCESS_MERCHANT_INFORMATION',
                    'TRACKING_SHIPMENT_READWRITE',
                    'VAULT',
                    'BILLING_AGREEMENT',
                ].join(','),
                displayMode: 'minibrowser',
                partnerLogoUrl: 'https://assets.shopware.com/media/logos/shopware_logo_blue.svg',
            },
        };
    },

    watch: {
        mode() {
            this.type = this.mode;
        },
        '$route.query'(query: Record<string, unknown>) {
            if (Object.hasOwn(query, 'ppOnboarding')) {
                this.completeOnboarding();
            }
        },
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        merchantInformationStore() {
            return Shopware.Store.get('swagPayPalMerchantInformation');
        },

        isSandbox() {
            return this.type === 'sandbox';
        },

        suffix() {
            return this.isSandbox ? 'Sandbox' : '';
        },

        returnUrl(): string {
            return `${window.location.origin}${window.location.pathname}#${this.$route.path}?ppOnboarding=${this.type}`;
        },

        requestParams() {
            return this.isSandbox ? this.sandbox : this.live;
        },

        onboardingUrl() {
            const url = new URL('/bizsignup/partner/entry', this.isSandbox ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com');

            url.search = (new URLSearchParams({
                ...this.commonRequestParams,
                ...this.requestParams,
                returnToPartnerUrl: this.returnUrl,
            })).toString();

            return url.href;
        },

        buttonTitle(): string {
            if (!this.settingsStore.get(`SwagPayPal.settings.clientSecret${this.suffix}`)) {
                return this.$t(`swag-paypal-onboarding-button.${this.type}.title`);
            }

            if (this.settingsStore.isSandbox === this.isSandbox && !this.merchantInformationStore.canPPCP) {
                return this.$t(`swag-paypal-onboarding-button.${this.type}.onboardingTitle`);
            }

            return this.$t(`swag-paypal-onboarding-button.${this.type}.changeTitle`);
        },

        callbackName(): `onboardingCallback${string}` {
            return `onboardingCallback${this.callbackId}`;
        },

        isDisabled() {
            return !this.acl.can('swag_paypal.editor') || this.isLoading || this.disabled;
        },

        classes() {
            return {
                'is--sandbox': this.isSandbox,
                'is--live': !this.isSandbox,
                'is--link': this.variant === 'link',
                'is--ghost': this.variant === 'ghost',
                'is--disabled': this.isDisabled,
            };
        },
    },

    mounted() {
        this.onMounted();
    },

    beforeUnmount() {
        delete window[this.callbackName];
    },

    methods: {
        onMounted() {
            if (!this.acl.can('swag_paypal.editor')) {
                return;
            }

            if (Object.hasOwn(this.$route.query, 'ppOnboarding')) {
                this.completeOnboarding();
            }

            window[this.callbackName] = (authCode, sharedId) => {
                this.fetchCredentials(authCode, sharedId);
            };

            this.loadPayPalScript();
        },

        createScriptElement(): HTMLScriptElement {
            const payPalScript = document.createElement('script');
            payPalScript.id = this.scriptId;
            payPalScript.type = 'text/javascript';
            payPalScript.src = this.scriptURL;
            payPalScript.async = true;

            document.head.appendChild(payPalScript);

            return payPalScript;
        },

        loadPayPalScript() {
            const el = document.getElementById(this.scriptId) ?? this.createScriptElement();

            if (window.PAYPAL) {
                this.isLoading = false;
                if (window.PAYPAL.apps.Signup.setup) {
                    window.PAYPAL.apps.Signup.setup();
                } else {
                    window.PAYPAL.apps.Signup.render();
                }
            } else {
                el.addEventListener('load', () => void this.renderPayPalButton(), false);
            }
        },

        async renderPayPalButton() {
            this.isLoading = true;

            // maybe another button instance already started loading and rendering
            if (!window.paypalScriptPromise) {
                window.PAYPAL!.apps.Signup.render = function proxyPPrender() {};
                window.paypalScriptPromise = this.waitForScriptsLoaded();
            }

            await window.paypalScriptPromise;

            this.isLoading = false;
        },

        // The partner.js does not provide a stub `setup` function like the `render` function.
        // The setup function is overriden as soon as all scripts are loaded properly.
        // Still, just in case we should stop waiting after 30sec.
        waitForScriptsLoaded(): Promise<void> {
            let tries = 0;

            const checkFn = (resolve: (() => void)) => {
                if (tries > 100) {
                    return resolve();
                }

                if (window.PAYPAL!.apps.Signup.render.name !== 'proxyPPrender') {
                    window.PAYPAL!.apps.Signup.render();
                    return resolve();
                }

                if (window.PAYPAL!.apps.Signup.setup) {
                    return resolve();
                }

                tries += 1;
                setTimeout(checkFn.bind(this, resolve), 300);
            };

            return new Promise<void>(checkFn.bind(this));
        },

        async fetchCredentials(authCode: string, sharedId: string) {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;

            const response = await this.SwagPayPalSettingsService.getApiCredentials(
                authCode,
                sharedId,
                this.requestParams.sellerNonce,
                this.isSandbox,
            ).catch((error: PayPal.ServiceError) => {
                this.createNotificationError({
                    title: this.$t('swag-paypal.notifications.credentials.title'),
                    message: this.$t('swag-paypal.notifications.credentials.errorMessage', {
                        message: this.createMessageFromError(error),
                    }),
                });

                return {} as Record<string, string>;
            });

            this.setConfig(response.client_id, response.client_secret, response.payer_id);

            this.isLoading = false;
        },

        setConfig(clientId?: string, clientSecret?: string, merchantPayerId?: string) {
            this.settingsStore.set(`SwagPayPal.settings.clientId${this.suffix}`, clientId);
            this.settingsStore.set(`SwagPayPal.settings.clientSecret${this.suffix}`, clientSecret);
            this.settingsStore.set(`SwagPayPal.settings.merchantPayerId${this.suffix}`, merchantPayerId);

            // First time onboarding
            if (!this.merchantInformationStore.canPPCP) {
                this.settingsStore.set('SwagPayPal.settings.sandbox', this.isSandbox);
            }

            this.$emit('onboarded');
        },

        completeOnboarding() {
            const { ppOnboarding, merchantIdInPayPal } = this.$route.query;

            this.$router.replace({ query: {} });

            if (!merchantIdInPayPal || ppOnboarding !== 'sandbox' && ppOnboarding !== 'live') {
                return;
            }

            const suffix = ppOnboarding === 'sandbox' ? 'Sandbox' : '';
            const merchantPayerId = String(merchantIdInPayPal);
            this.settingsStore.set(
                `SwagPayPal.settings.merchantPayerId${suffix}`,
                merchantPayerId,
            );

            this.$emit('onboarded');
        },
    },
});

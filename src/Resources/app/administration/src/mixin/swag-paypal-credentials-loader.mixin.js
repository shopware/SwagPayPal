const { Mixin } = Shopware;
const { debug } = Shopware.Utils;

Mixin.register('swag-paypal-credentials-loader', {

    inject: ['SwagPayPalApiCredentialsService'],

    data() {
        return {
            isGetCredentialsSuccessful: false,
            nonceLive: `${Shopware.Utils.createId()}${Shopware.Utils.createId()}`,
            nonceSandbox: `${Shopware.Utils.createId()}${Shopware.Utils.createId()}`,
            payPalPartnerIdLive: 'DYKPBPEAW5JNA',
            payPalPartnerClientIdLive: 'AR1aQ13lHxH1c6b3CDd8wSY6SWad2Lt5fv5WkNIZg-qChBoGNfHr2kT180otUmvE_xXtwkgahXUBBurW',
            payPalPartnerIdSandbox: '45KXQA7PULGAG',
            payPalPartnerClientIdSandbox: 'AQ9g8qMYHpE8s028VCq_GO3Roy9pjeqGDjKTkR_sxzX0FtncBb3QUWbFtoQMtdpe2lG9NpnDT419dK8s',

            requestParams: {
                channelId: 'partner',
                product: 'payment_methods',
                capabilities: 'PAY_UPON_INVOICE',
                integrationType: 'FO',
                features: [
                    'PAYMENT',
                    'REFUND',
                    'READ_SELLER_DISPUTE',
                    'UPDATE_SELLER_DISPUTE',
                    'ADVANCED_TRANSACTIONS_SEARCH',
                    'ACCESS_MERCHANT_INFORMATION',
                    'TRACKING_SHIPMENT_READWRITE',
                ],
                displayMode: 'minibrowser',
                partnerLogoUrl: 'https://assets.shopware.com/media/logos/shopware_logo_blue.svg',
            },
        };
    },

    computed: {
        onboardingUrlLive() {
            const params = this.createRequestParameter({
                partnerId: this.payPalPartnerIdLive,
                partnerClientId: this.payPalPartnerClientIdLive,
                returnToPartnerUrl: this.returnUrl(),
                sellerNonce: this.nonceLive,
            });

            return `https://www.paypal.com/US/merchantsignup/partner/onboardingentry?${params.toString()}`;
        },
        onboardingUrlSandbox() {
            const params = this.createRequestParameter({
                partnerId: this.payPalPartnerIdSandbox,
                partnerClientId: this.payPalPartnerClientIdSandbox,
                returnToPartnerUrl: this.returnUrl(),
                sellerNonce: this.nonceSandbox,
            });

            return `https://www.sandbox.paypal.com/bizsignup/partner/entry?${params.toString()}`;
        },
    },

    created() {
        this.$root.$on('paypal-onboarding-finish', this.getPayPalCredentials);
        window.onboardingCallbackLive = function onboardingCallback(authCode, sharedId) {
            Shopware.Application.getApplicationRoot().$emit(
                'paypal-onboarding-finish',
                { authCode, sharedId, sandbox: false },
            );
        };
        window.onboardingCallbackSandbox = function onboardingCallbackSandbox(authCode, sharedId) {
            Shopware.Application.getApplicationRoot().$emit(
                'paypal-onboarding-finish',
                { authCode, sharedId, sandbox: true },
            );
        };
    },

    mounted() {
        this.createScript();
    },

    destroyed() {
        this.$root.$off('paypal-onboarding-finish');
    },

    methods: {
        returnUrl() {
            return `${window.location.origin}${window.location.pathname}#${this.$route.path}`;
        },

        createRequestParameter(config = {}) {
            const params = { ...this.requestParams, ...config };
            return Object.keys(params).reduce((accumulator, key) => {
                let value = params[key];

                if (Array.isArray(value)) {
                    value = value.join(',');
                }
                accumulator.append(key, value);

                return accumulator;
            }, new URLSearchParams());
        },

        createScript() {
            const id = 'paypal-js';
            if (!document.getElementById(id)) {
                const payPalScriptUrl = 'https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js';
                const payPalScript = document.createElement('script');
                payPalScript.id = id;
                payPalScript.type = 'text/javascript';
                payPalScript.src = payPalScriptUrl;
                payPalScript.async = true;
                payPalScript.addEventListener('load', this.renderPayPalButton.bind(this), false);

                document.head.appendChild(payPalScript);
            } else if (window.PAYPAL) {
                this.renderPayPalButton();
            }
        },

        renderPayPalButton() {
            // The original render function inside the partner.js is overwritten here.
            // The function gets overwritten again, as soon as PayPals signup.js is loaded.
            // A loop is created and the render() function is executed until the real render() function is available.
            // PayPal does originally nearly the same, but only once and not in a loop.
            // If the signup.js is loaded to slow the button is not rendered.
            window.PAYPAL.apps.Signup.render = function proxyPPrender() {
                if (window.PAYPAL.apps.Signup.timeout) {
                    clearTimeout(window.PAYPAL.apps.Signup.timeout);
                }

                window.PAYPAL.apps.Signup.timeout = setTimeout(window.PAYPAL.apps.Signup.render, 300);
            };

            window.PAYPAL.apps.Signup.render();
        },

        getPayPalCredentials({ authCode, sharedId, sandbox }) {
            if (this.isLoading) {
                return Promise.resolve(false);
            }

            this.isGetCredentialsSuccessful = null;

            this.isLoading = true;

            return this.SwagPayPalApiCredentialsService.getApiCredentials(
                authCode,
                sharedId,
                sandbox ? this.nonceSandbox : this.nonceLive,
                sandbox,
            ).then((response) => {
                this.isGetCredentialsSuccessful = true;
                this.onPayPalCredentialsLoadSuccess(response.client_id, response.client_secret, sandbox);
            }).catch(() => {
                this.isGetCredentialsSuccessful = false;
                this.onPayPalCredentialsLoadFailed(sandbox);
            }).finally(() => {
                this.isLoading = false;
            });
        },

        /**
         *
         * @param clientId string
         * @param clientSecret string
         * @param sandbox bool
         */
        onPayPalCredentialsLoadSuccess() {
            // needs to be implemented by using component
            debug.warn(
                'swag-paypal-credentials-loader Mixin',
                'When using the paypal-credentials-loader mixin ' +
                'you have to implement your custom "onPayPalCredentialsLoadSuccess()" method.',
            );
        },

        /**
         *
         * @param sandbox bool
         */
        onPayPalCredentialsLoadFailed() {
            // needs to be implemented by using component
            debug.warn(
                'swag-paypal-credentials-loader Mixin',
                'When using the paypal-credentials-loader mixin ' +
                'you have to implement your custom "onPayPalCredentialsLoadFailed()" method.',
            );
        },

    },
});

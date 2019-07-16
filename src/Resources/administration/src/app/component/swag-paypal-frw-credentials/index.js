import { Component, Mixin } from 'src/core/shopware';
import utils from 'src/core/service/util.service';
import template from './swag-paypal-frw-credentials.html.twig';
import './swag-paypal-frw.scss';

const payPalPartnerIdLive = 'W8HDQ6LB42CJW';
const payPalPartnerClientIdLive = 'AVTKpaE_t1zRCDfiJOP1ZYMAW0S_IvASFOIhhbeszRUFY0vsFIsGrt_FFsgHKU4VJiqub-tI30dpnANJ';
const payPalPartnerIdSandbox = 'J425NKDMLL4YA';
const payPalPartnerClientIdSandbox = 'AdRxw_8f4e2MOEduZB6D6ZOkdjnbR3SQJ1dQP3Y6GDLkxK0g4j0km2V2tRjoVDe0T2ZqQX6NlzpKsBwE';

window.onboardedCallback = function onboardedCallback(authCode, sharedId) {
    Shopware.Application.getApplicationRoot().$emit('paypal-onboarding-finish', { authCode, sharedId });
};

Component.register('swag-paypal-frw-credentials', {
    template,

    inject: ['systemConfigApiService', 'SwagPayPalApiCredentialsService', 'addNextCallback'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
    },

    data() {
        return {
            isLoading: false,
            config: {},
            authCode: '',
            sharedId: '',
            nonce: `${utils.createId()}${utils.createId()}`,
            fetchedSuccessful: null,
            lockGetApiCredentials: false
        };
    },

    computed: {
        onBoardingUrl() {
            const $returnUrl = `${window.location.origin}${window.location.pathname}#${this.$route.path}`;

            const urlParams = new URLSearchParams();
            urlParams.append('channelId', 'partner');
            urlParams.append('partnerId', this.payPalPartnerId);
            urlParams.append('productIntentId', 'addipmt');
            urlParams.append('integrationType', 'FO');
            urlParams.append('features', 'READ_SELLER_DISPUTED,UPDATE_SELLER_DISPUTE,ADVANCED_TRANSACTIONS_SEARCH');
            urlParams.append('partnerClientId', this.payPalPartnerClientId);
            urlParams.append('returnToPartnerUrl', $returnUrl);
            urlParams.append('displayMode', 'minibrowser');
            urlParams.append('sellerNonce', this.nonce);

            return `${this.payPalHost}/US/merchantsignup/partner/onboardingentry?${urlParams.toString()}`;
        },
        payPalPartnerId() {
            return this.sandboxMode ? payPalPartnerIdSandbox : payPalPartnerIdLive;
        },
        payPalPartnerClientId() {
            return this.sandboxMode ? payPalPartnerClientIdSandbox : payPalPartnerClientIdLive;
        },
        payPalHost() {
            return this.sandboxMode ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
        },
        sandboxMode() {
            return this.config['SwagPayPal.settings.sandbox'] || false;
        }
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.mountedComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            this.fetchPayPalConfig();

            this.addNextCallback(this.onClickNext);

            this.$root.$on('paypal-onboarding-finish', this.getPayPalCredentials);
        },
        mountedComponent() {
            this.createScript();
        },
        destroyedComponent() {
            this.$root.$off('paypal-onboarding-finish');
        },
        getPayPalCredentials({ authCode, sharedId }) {
            if (this.lockGetApiCredentials) {
                return;
            }

            this.lockGetApiCredentials = true;
            this.fetchedSuccessful = null;
            this.isLoading = true;
            this.SwagPayPalApiCredentialsService.getApiCredentials(
                authCode,
                sharedId,
                this.nonce,
                this.config['SwagPayPal.settings.sandbox']
            ).then((response) => {
                this.$set(this.config, 'SwagPayPal.settings.clientId', response.client_id);
                this.$set(this.config, 'SwagPayPal.settings.clientSecret', response.client_secret);
                this.fetchedSuccessful = true;
            }).catch(() => {
                this.$set(this.config, 'SwagPayPal.settings.clientId', '');
                this.$set(this.config, 'SwagPayPal.settings.clientSecret', '');
                this.fetchedSuccessful = false;
                this.createNotificationError({
                    title: this.$tc('swag-paypal-frw-credentials.titleFetchedError'),
                    message: this.$tc('swag-paypal-frw-credentials.messageFetchedError'),
                    duration: 10000
                });
            }).finally(() => {
                this.isLoading = false;
                this.lockGetApiCredentials = false;
            });
        },
        onClickNext() {
            // Skip if no credentials have been provided
            if (!this.config['SwagPayPal.settings.clientId'] || !this.config['SwagPayPal.settings.clientSecret']) {
                this.createNotificationError({
                    title: this.$tc('swag-paypal-frw-credentials.titleNoCredentials'),
                    message: this.$tc('swag-paypal-frw-credentials.messageNoCredentials')
                });
                return Promise.resolve(true);
            }
            // Do not test the credentials if they have been fetched from the PayPal api
            if (this.fetchedSuccessful) {
                return this.saveConfig().then(() => {
                    return Promise.resolve(false);
                }).catch(() => {
                    return Promise.resolve(true);
                });
            }

            return this.testApiCredentials().then(result => {
                if (result === 'success') {
                    return this.saveConfig().then(() => {
                        return Promise.resolve(false);
                    }).catch(() => {
                        return Promise.resolve(true);
                    });
                }
                return Promise.resolve(true);
            });
        },
        fetchPayPalConfig() {
            this.isLoading = true;
            return this.systemConfigApiService.getValues('SwagPayPal.settings', null)
                .then(values => {
                    this.config = values;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        saveConfig() {
            this.isLoading = true;
            return this.systemConfigApiService.saveValues(this.config, null).then(() => {
                this.isLoading = false;
            });
        },
        testApiCredentials() {
            this.isLoading = true;
            return this.SwagPayPalApiCredentialsService.validateApiCredentials(
                this.config['SwagPayPal.settings.clientId'],
                this.config['SwagPayPal.settings.clientSecret'],
                this.config['SwagPayPal.settings.sandbox']
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.isLoading = false;
                    return 'success';
                }

                return 'error';
            }).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    let message = '<ul>';
                    errorResponse.response.data.errors.forEach((error) => {
                        message = `${message}<li>${error.detail}</li>`;
                    });
                    message += '</ul>';
                    this.createNotificationError({
                        title: this.$tc('swag-paypal-frw-credentials.titleTestError'),
                        message: message
                    });
                    this.isLoading = false;
                }

                return 'error';
            });
        },
        onCredentialsChanged() {
            if (this.fetchedSuccessful !== null) {
                this.fetchedSuccessful = null;
            }
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
            }
        },
        renderPayPalButton() {
            // We override the original render function inside the partner.js here.
            // The function gets overridden again, as soon as PayPals signup.js is loaded.
            // We create a loop and execute the render() function until the real render() function is available
            // PayPal does originally nearly the same, but only once and not in a loop. If the signup.js is loaded to slow
            // the button is not rendered.
            window.PAYPAL.apps.Signup.render = function proxyPPrender() {
                if (window.PAYPAL.apps.Signup.timeout) {
                    clearTimeout(window.PAYPAL.apps.Signup.timeout);
                }

                window.PAYPAL.apps.Signup.timeout = setTimeout(window.PAYPAL.apps.Signup.render, 300);
            };

            window.PAYPAL.apps.Signup.render();
        }
    }
});

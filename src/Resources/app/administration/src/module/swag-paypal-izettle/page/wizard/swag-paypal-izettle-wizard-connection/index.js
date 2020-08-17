import template from './swag-paypal-izettle-wizard-connection.html.twig';
import './swag-paypal-izettle-wizard-connection.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-connection', {
    template,

    inject: [
        'SwagPayPalIZettleSettingApiService'
    ],

    mixins: [
        'notification'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        cloneSalesChannelId: {
            type: String,
            required: false
        },
        isLoading: {
            type: Boolean,
            required: false,
            default() {
                return false;
            }
        }
    },

    data() {
        return {
            isConnecting: false
        };
    },

    computed: {
        apiKeyUrl() {
            return this.SwagPayPalIZettleSettingApiService.generateApiUrl();
        }
    },

    watch: {
        'salesChannel.extensions.paypalIZettleSalesChannel.apiKey'(key) {
            if (!key) {
                return;
            }

            this.updateButtons();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateButtons();
            this.setTitle();
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.connection.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToConnectionSuccess,
                    disabled: this.isLoading || !(this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeToConnectionSuccess() {
            this.toggleLoadingState(true);
            const apiKey = this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey;

            this.SwagPayPalIZettleSettingApiService.validateApiCredentials(apiKey).then((response) => {
                if (response.credentialsValid === true) {
                    this.toggleLoadingState(false);
                    this.$router.push({ name: 'swag.paypal.izettle.wizard.connectionSuccess' });
                }
            }).catch((errorResponse) => {
                this.catchAuthentificationError(errorResponse);
            }).finally(() => {
                this.toggleLoadingState(false);
            });
        },

        catchAuthentificationError(errorResponse) {
            if (errorResponse.response.data && errorResponse.response.data.errors) {
                const message = errorResponse.response.data.errors.map((error) => {
                    return error.detail;
                }).join(' / ');

                this.createNotificationError({
                    message
                });
            }
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        }
    }
});

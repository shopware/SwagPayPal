import template from './swag-paypal-pos-wizard-connection.html.twig';
import './swag-paypal-pos-wizard-connection.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-wizard-connection', {
    template,

    inject: [
        'SwagPayPalPosSettingApiService'
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
            isConnecting: false,
            apiKeyUrl: this.SwagPayPalPosSettingApiService.generateApiUrl()
        };
    },

    watch: {
        'salesChannel.extensions.paypalPosSalesChannel.apiKey'(key) {
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.connection.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToConnectionSuccess,
                    disabled: this.isLoading || !(this.salesChannel.extensions.paypalPosSalesChannel.apiKey)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeToConnectionSuccess() {
            this.toggleLoadingState(true);
            const apiKey = this.salesChannel.extensions.paypalPosSalesChannel.apiKey;

            this.SwagPayPalPosSettingApiService.validateApiCredentials(apiKey).then((response) => {
                if (response.credentialsValid === true) {
                    this.toggleLoadingState(false);
                    this.$router.push({ name: 'swag.paypal.pos.wizard.connectionSuccess' });
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

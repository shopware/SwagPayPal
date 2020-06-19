import template from './swag-paypal-izettle-wizard-connection.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-wizard-connection', {
    template,

    inject: [
        'SwagPayPalIZettleSettingApiService'
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: true
        },
        salesChannel: {
            type: Object,
            required: true
        },
        storefrontSalesChannelId: {
            type: String,
            required: false
        },
        isTestingCredentials: {
            type: Boolean,
            required: false
        },
        isTestCredentialsSuccessful: {
            type: Boolean,
            required: false
        }
    },

    computed: {
        apiKeyUrl() {
            return this.SwagPayPalIZettleSettingApiService.generateApiUrl();
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
                    action: 'swag.paypal.izettle.wizard.sales-channel',
                    disabled: !(this.salesChannel.extensions.paypalIZettleSalesChannel.apiKey)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },

        onTestCredentials() {
            this.$emit('test-credentials');
        }
    }
});

import template from './swag-paypal-pos-wizard-connection-success.html.twig';
import './swag-paypal-pos-wizard-connection-success.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-wizard-connection-success', {
    template,

    inject: [
        'repositoryFactory',
        'SwagPayPalPosSettingApiService'
    ],

    mixin: [
        'placeholder'
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
        saveSalesChannel: {
            type: Function,
            required: true
        }
    },

    data() {
        return {
            iZettleData: null,
            isFetchingInformation: true
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        iZettleUser() {
            if (this.isFetchingInformation) {
                const firstName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeFirstName');
                const lastName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeLastName');
                const mail = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeMail');

                return {
                    firstName,
                    lastName,
                    fullName: `${firstName} ${lastName}`,
                    mail
                };
            }
            const parts = this.iZettleData.merchantInformation.name.split(' ');

            return {
                firstName: parts[0],
                lastName: parts[parts.length - 1],
                fullName: this.iZettleData.merchantInformation.name,
                mail: this.iZettleData.merchantInformation.contactEmail
            };
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isFetchingInformation = true;
            this.updateButtons();
            this.setTitle();

            this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel).then((response) => {
                this.iZettleData = response;

                return this.saveSalesChannel();
            }).finally(() => {
                this.isFetchingInformation = false;
                this.updateButtons();
            });
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.connectionSuccess.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToCustomization,
                    disabled: this.isFetchingInformation
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeToCustomization() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.customization',
                params: { id: this.salesChannel.id }
            });
        },

        onDisconnect() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.connectionDisconnect',
                params: { id: this.salesChannel.id }
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        }
    }
});

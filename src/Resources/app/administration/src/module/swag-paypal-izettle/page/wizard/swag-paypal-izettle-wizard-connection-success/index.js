import template from './swag-paypal-izettle-wizard-connection-success.html.twig';
import './swag-paypal-izettle-wizard-connection-success.scss';

const { Component, Context } = Shopware;

Component.register('swag-paypal-izettle-wizard-connection-success', {
    template,

    inject: [
        'repositoryFactory',
        'SwagPayPalIZettleSettingApiService'
    ],

    mixin: [
        'placeholder',
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
                const firstName = this.$tc('swag-paypal-izettle.wizard.connection-success.fakeFirstName');
                const lastName = this.$tc('swag-paypal-izettle.wizard.connection-success.fakeLastName');
                const mail = this.$tc('swag-paypal-izettle.wizard.connection-success.fakeMail');

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
                mail: this.iZettleData.merchantInformation.receiptEmail
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

            this.SwagPayPalIZettleSettingApiService.fetchInformation(this.salesChannel).then((response) => {
                this.iZettleData = response;

                return this.saveSalesChannel();
            }).finally(() => {
                this.isFetchingInformation = false;
                this.updateButtons();
            });
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.connection-success.modalTitle'));
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
                name: 'swag.paypal.izettle.wizard.customization',
                params: { id: this.salesChannel.id }
            });
        },

        onDisconnect() {
            this.salesChannelRepository.delete(this.salesChannel.id, Context.api).then(() => {
                this.$emit('recreate-sales-channel');
                this.forceUpdate();

                this.$router.push({ name: 'swag.paypal.izettle.wizard.connection' });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('swag-paypal-izettle.wizard.connection-success.disconnectErrorMessage')
                });
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        }
    }
});

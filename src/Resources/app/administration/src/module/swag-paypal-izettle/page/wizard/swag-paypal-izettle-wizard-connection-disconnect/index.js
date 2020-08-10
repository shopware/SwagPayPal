import template from './swag-paypal-izettle-wizard-connection-disconnect.html.twig';
import './swag-paypal-izettle-wizard-connection-disconnect.scss';

const { Component, Context } = Shopware;

Component.register('swag-paypal-izettle-wizard-connection-disconnect', {
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
                const firstName = this.$tc('swag-paypal-izettle.wizard.connectionSuccess.fakeFirstName');
                const lastName = this.$tc('swag-paypal-izettle.wizard.connectionSuccess.fakeLastName');
                const mail = this.$tc('swag-paypal-izettle.wizard.connectionSuccess.fakeMail');

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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.connectionDisconnect.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'cancel',
                    label: this.$tc('global.default.cancel'),
                    position: 'right',
                    action: this.routeBackToConnectionSuccess,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('swag-paypal-izettle.wizard.connectionDisconnect.disconnectButton'),
                    position: 'right',
                    variant: 'danger',
                    action: this.onDisconnect,
                    disabled: this.isFetchingInformation
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToConnectionSuccess() {
            this.$router.push({
                name: 'swag.paypal.izettle.wizard.connectionSuccess',
                params: { id: this.salesChannel.id }
            });
        },

        onDisconnect() {
            // ToDo PPI-22 - The module should go into a disconnected state instead of deleting the whole saleschannel.
            this.salesChannelRepository.delete(this.salesChannel.id, Context.api).then(() => {
                this.$emit('recreate-sales-channel');
                this.forceUpdate();

                this.$router.push({ name: 'swag.paypal.izettle.wizard.connection' });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('swag-paypal-izettle.wizard.connectionDisconnect.disconnectErrorMessage')
                });
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        }
    }
});

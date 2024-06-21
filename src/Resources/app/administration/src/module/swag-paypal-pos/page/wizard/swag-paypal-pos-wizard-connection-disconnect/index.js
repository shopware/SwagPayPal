import template from './swag-paypal-pos-wizard-connection-disconnect.html.twig';
import './swag-paypal-pos-wizard-connection-disconnect.scss';

const { Component, Context } = Shopware;

Component.register('swag-paypal-pos-wizard-connection-disconnect', {
    template,

    inject: [
        'repositoryFactory',
        'SwagPayPalPosSettingApiService',
    ],

    mixin: [
        Shopware.Mixin.getByName('placeholder'),
        Shopware.Mixin.getByName('notification'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true,
        },
        cloneSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
        saveSalesChannel: {
            type: Function,
            required: true,
        },
    },

    data() {
        return {
            posData: null,
            isFetchingInformation: true,
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        posUser() {
            if (this.isFetchingInformation) {
                const firstName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeFirstName');
                const lastName = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeLastName');
                const mail = this.$tc('swag-paypal-pos.wizard.connectionSuccess.fakeMail');

                return {
                    firstName,
                    lastName,
                    fullName: `${firstName} ${lastName}`,
                    mail,
                };
            }
            const parts = this.posData.merchantInformation.name.split(' ');

            return {
                firstName: parts[0],
                lastName: parts[parts.length - 1],
                fullName: this.posData.merchantInformation.name,
                mail: this.posData.merchantInformation.contactEmail,
            };
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isFetchingInformation = true;
            this.updateButtons();
            this.setTitle();

            this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel, true).then((response) => {
                this.posData = response;
            }).finally(() => {
                this.isFetchingInformation = false;
                this.updateButtons();
            });
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.connectionDisconnect.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'cancel',
                    label: this.$tc('global.default.cancel'),
                    position: 'right',
                    action: this.routeBackToConnectionSuccess,
                    disabled: false,
                },
                {
                    key: 'next',
                    label: this.$tc('swag-paypal-pos.wizard.connectionDisconnect.disconnectButton'),
                    position: 'right',
                    variant: 'danger',
                    action: this.onDisconnect,
                    disabled: this.isFetchingInformation,
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToConnectionSuccess() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.connectionSuccess',
                params: { id: this.salesChannel.id },
            });
        },

        onDisconnect() {
            // ToDo PPI-22 - The module should go into a disconnected state instead of deleting the whole saleschannel.
            this.salesChannelRepository.delete(this.salesChannel.id, Context.api).then(() => {
                // Forces the sw-admin-menu component to refresh the SalesChannel list
                this.$root.$emit('sales-channel-change');

                this.$emit('recreate-sales-channel');
                this.forceUpdate();

                this.$router.push({ name: 'swag.paypal.pos.wizard.connection' });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('swag-paypal-pos.wizard.connectionDisconnect.disconnectErrorMessage'),
                });
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },
    },
});

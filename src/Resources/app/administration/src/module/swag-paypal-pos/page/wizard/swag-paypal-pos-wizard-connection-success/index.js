import template from './swag-paypal-pos-wizard-connection-success.html.twig';
import './swag-paypal-pos-wizard-connection-success.scss';

const { Component, Context } = Shopware;

Component.register('swag-paypal-pos-wizard-connection-success', {
    template,

    inject: [
        'repositoryFactory',
        'SwagPayPalPosSettingApiService',
    ],

    mixin: [
        'placeholder',
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

            return this.SwagPayPalPosSettingApiService.fetchInformation(this.salesChannel, true).then((response) => {
                this.posData = response;

                if (this.salesChannel.languageId === null) {
                    this.salesChannel.languageId = Context.api.systemLanguageId;
                    this.salesChannel.languages.push({
                        id: Context.api.systemLanguageId,
                    });
                }

                return this.saveSalesChannel(false, true);
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
                    disabled: this.isFetchingInformation,
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeToCustomization() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.customization',
                params: { id: this.salesChannel.id },
            });
        },

        onDisconnect() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.connectionDisconnect',
                params: { id: this.salesChannel.id },
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.updateButtons();
        },
    },
});

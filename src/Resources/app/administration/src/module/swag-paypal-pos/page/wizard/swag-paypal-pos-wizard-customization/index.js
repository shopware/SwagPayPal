import template from './swag-paypal-pos-wizard-customization.html.twig';
import './swag-paypal-pos-wizard-customization.scss';

const { Component } = Shopware;
const { EntityCollection } = Shopware.Data;

Component.register('swag-paypal-pos-wizard-customization', {
    template,

    inject: [
        'repositoryFactory',
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
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        languageRepository() {
            return this.repositoryFactory.create('language');
        },
    },

    watch: {
        'isLoading'(loading) {
            if (loading) {
                return;
            }

            this.updateButtons();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateButtons();
            this.setTitle();
            this.changeLanguage();
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.customization.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToConnectionSuccess,
                    disabled: false,
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToProductSelection,
                    disabled: this.nextButtonDisabled(),
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        nextButtonDisabled() {
            return this.isLoading
                || !(this.salesChannel.name)
                || !(this.salesChannel.languageId)
                || !(this.salesChannel.extensions.paypalPosSalesChannel.mediaDomain);
        },

        routeBackToConnectionSuccess() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.connectionSuccess',
                params: { id: this.salesChannel.id },
            });
        },

        routeToProductSelection() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.productSelection',
                params: { id: this.salesChannel.id },
            });
        },

        forceUpdate() {
            this.$forceUpdate();
            this.$nextTick().then(() => {
                this.updateButtons();
            });
        },

        changeLanguage() {
            this.salesChannel.languages = new EntityCollection('language', 'language', Shopware.Context.api);
            this.salesChannel.languages.push({
                id: this.salesChannel.languageId,
            });
            this.$forceUpdate();
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        },
    },
});

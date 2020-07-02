import template from './swag-paypal-izettle-wizard-customization.html.twig';

const { Component, Mixin } = Shopware;

Component.register('swag-paypal-izettle-wizard-customization', {
    template,

    mixins: [
        Mixin.getByName('placeholder')
    ],

    inject: [
        'repositoryFactory'
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
        cloneSalesChannelId: {
            type: String,
            required: false
        }
    },

    computed: {
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
            this.$emit('frw-set-title', this.$tc('swag-paypal-izettle.wizard.customization.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: 'swag.paypal.izettle.wizard.connection',
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: 'swag.paypal.izettle.wizard.product-selection',
                    disabled: !(this.salesChannel.name)
                           || !(this.salesChannel.extensions.paypalIZettleSalesChannel.salesChannelDomainId)
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        forceUpdate() {
            this.$forceUpdate();
            this.$nextTick().then(() => {
                this.updateButtons();
            });
        }
    }
});

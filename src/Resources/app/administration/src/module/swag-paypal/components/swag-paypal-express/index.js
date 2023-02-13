import template from './swag-paypal-express.html.twig';

const { Component } = Shopware;

Component.register('swag-paypal-express', {
    template,

    inject: [
        'acl',
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
            default: () => { return {}; },
        },
        allConfigs: {
            type: Object,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

    computed: {
        buttonColorOptions() {
            return [
                {
                    id: 'blue',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.blue'),
                },
                {
                    id: 'black',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.black'),
                },
                {
                    id: 'gold',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.gold'),
                },
                {
                    id: 'silver',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.silver'),
                },
                {
                    id: 'white',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonColor.options.white'),
                },
            ];
        },
        buttonShapeOptions() {
            return [
                {
                    id: 'pill',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.pill'),
                },
                {
                    id: 'rect',
                    name: this.$tc('swag-paypal.settingForm.express.ecsButtonShape.options.rect'),
                },
            ];
        },
    },

    methods: {
        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },

        preventSave(mode) {
            this.$emit('preventSave', mode);
        },
    },
});

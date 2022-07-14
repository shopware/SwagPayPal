import template from './sw-dashboard-index.html.twig';

const { Component } = Shopware;

Component.override('sw-dashboard-index', {
    template,

    inject: [
        'systemConfigApiService',
    ],

    data() {
        return {
            /**
             * @private
             */
            payPalSystemConfig: null,
        };
    },

    computed: {
        /**
         * @private
         */
        showPayPalBanner() {
            if (!this.payPalSystemConfig) {
                return false;
            }

            return (this.payPalSystemConfig['SwagPayPal.settings.plusCheckoutEnabled']
                    || new Date() < new Date('2022-07-23'))
                && (this.payPalSystemConfig['SwagPayPal.settings.clientId']
                    || this.payPalSystemConfig['SwagPayPal.settings.clientIdSandbox']);
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.systemConfigApiService.getValues('SwagPayPal.settings').then((config) => {
                this.payPalSystemConfig = config;
            });
        },
    },
});

export default Shopware.Mixin.register('swag-paypal-pos-log-label', Shopware.Component.wrapComponentConfig({
    methods: {
        /**
         * Returns the corresponding sw-label variant for a Zettle log
         */
        getLabelVariant(level: number): 'success' | 'info' | 'warning' | 'danger' {
            if (level >= 400) {
                return 'danger';
            }

            if (level >= 300) {
                return 'warning';
            }

            if (level > 200) {
                return 'info';
            }

            return 'success';
        },

        /**
         * Returns the corresponding translation path for a Zettle log
         */
        getLabel(level: number): string {
            if (level >= 300) {
                return 'swag-paypal-pos.detail.logs.states.failed';
            }

            return 'swag-paypal-pos.detail.logs.states.success';
        },
    },
}));

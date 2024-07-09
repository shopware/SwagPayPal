export default Shopware.Mixin.register('swag-paypal-pos-log-label', {
    methods: {
        /**
         * Returns the corresponding sw-label variant for a Zettle log
         *
         * @param {Number} level
         * @returns {string}
         */
        getLabelVariant(level) {
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
         *
         * @param {Number} level
         * @returns {string}
         */
        getLabel(level) {
            if (level >= 300) {
                return 'swag-paypal-pos.detail.logs.states.failed';
            }

            return 'swag-paypal-pos.detail.logs.states.success';
        },
    },
});

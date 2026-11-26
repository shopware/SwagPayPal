import template from './swag-paypal-settings-advanced.html.twig';
import './swag-paypal-settings-advanced.scss';
import { COUNTRY_OVERRIDES, LOG_LEVELS } from 'SwagPayPal/constant/swag-paypal-settings.constant';

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        countryOverrideOptions() {
            const options = COUNTRY_OVERRIDES.map((locale) => ({
                value: locale,
                label: this.$t(`locale.${locale}`),
            })).sort((a, b) => a.label.localeCompare(b.label));

            return [{
                value: null,
                label: this.$t('swag-paypal-settings.crossBorder.buyerCountryAuto'),
            }, ...options];
        },

        logLevelOptions() {
            return Object.entries(LOG_LEVELS).map(([name, value]) => ({
                value,
                label: this.$t(`swag-paypal-settings.logs.level.${name}`),
            }));
        },
    },
});

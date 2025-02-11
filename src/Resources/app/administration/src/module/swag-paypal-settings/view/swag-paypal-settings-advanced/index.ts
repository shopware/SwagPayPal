import template from './swag-paypal-settings-advanced.html.twig';
import './swag-paypal-settings-advanced.scss';
import { COUNTRY_OVERRIDES } from '../../../../constant/swag-paypal-settings.constant';

export default Shopware.Component.wrapComponentConfig({
    template,

    compatConfig: Shopware.compatConfig,

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
    },
});

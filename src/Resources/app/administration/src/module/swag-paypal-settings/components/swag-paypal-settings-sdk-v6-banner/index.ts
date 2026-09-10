import template from './swag-paypal-settings-sdk-v6-banner.html.twig';
import './swag-paypal-settings-sdk-v6-banner.scss';

/**
 * @private
 * @deprecated tag:v11.0.0 - Will be removed without replacement. Remove snippets too.
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        paymentMethod: {
            type: Object as PropType<TEntity<'payment_method'>>,
            required: false,
            default: null,
        },
    },

    data(): {
        hidden: boolean;
    } {
        return {
            hidden: localStorage.getItem('swag-paypal-settings-sdk-v6-banner') === 'true',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        show() {
            return !this.hidden && !this.settingsStore.get('SwagPayPal.settings.sdkV6Enabled');
        },
    },

    methods: {
        onCloseAlert() {
            this.hidden = true;
            localStorage.setItem('swag-paypal-settings-sdk-v6-banner', 'true');
        },
    },
});

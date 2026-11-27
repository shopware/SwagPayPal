import template from './swag-paypal-settings-diagnostics-section.html.twig';
import './swag-paypal-settings-diagnostics-section.scss';

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        title: {
            type: String,
            required: true,
        },

        description: {
            type: String,
            required: false,
            default: '',
        },
    },
});

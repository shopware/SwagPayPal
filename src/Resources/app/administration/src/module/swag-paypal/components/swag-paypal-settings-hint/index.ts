import './swag-paypal-settings-hint.scss';
import template from './swag-paypal-settings-hint.html.twig';

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        hintText: {
            type: String,
            required: true,
        },
    },
});

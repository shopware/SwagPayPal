import './swag-paypal-settings-hint.scss';
import template from './swag-paypal-settings-hint.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        hintText: {
            type: String,
            required: true,
        },
    },
});

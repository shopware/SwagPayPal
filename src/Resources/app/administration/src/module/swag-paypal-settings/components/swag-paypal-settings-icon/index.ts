import template from './swag-paypal-settings-icon.html.twig';
import './swag-paypal-settings-icon.scss';
import IconsPaypalMulticolor from 'SwagPayPal/app/assets/icons/svg/icons-paypal-multicolor.svg?component';

export default Shopware.Component.wrapComponentConfig({
    template,

    components: {
        IconsPaypalMulticolor,
    },
});

import template from './swag-paypal-settings-icon.html.twig';
import './swag-paypal-settings-icon.scss';
import IconsPaypalMulticolor from '../../../../app/assets/icons/svg/icons-paypal-multicolor.svg?component';

export default Shopware.Component.wrapComponentConfig({
    template,

    components: {
        IconsPaypalMulticolor,
    },

    compatConfig: Shopware.compatConfig,
});

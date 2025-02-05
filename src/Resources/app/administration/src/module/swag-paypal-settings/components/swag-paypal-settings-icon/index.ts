import template from './swag-paypal-settings-icon.html.twig';
import './swag-paypal-settings-icon.scss';
import IconsPaypalMulticolor from 'SwagPayPal/app/assets/icons/svg/icons-paypal-multicolor.svg?component';

export default Shopware.Component.wrapComponentConfig({
    template,

    components: {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
        IconsPaypalMulticolor,
    },

    compatConfig: Shopware.compatConfig,
});

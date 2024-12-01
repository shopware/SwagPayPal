import template from './swag-paypal-checkout-domain-association.html.twig';
import './swag-paypal-checkout-domain-association.scss';

/**
 * @deprecated tag:v10.0.0 - Will be replaced by `swag-paypal-method-domain-association`
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    props: {
        isSandbox: {
            required: true,
        },
    },

    computed: {
        domainAssociationLink() {
            return this.isSandbox
                ? 'https://www.sandbox.paypal.com/uccservicing/apm/applepay'
                : 'https://www.paypal.com/uccservicing/apm/applepay';
        },
    },

    methods: {
        onCloseAlert() {
            this.$emit('hideDomainAssociationEvent');
        },
    },
});

import template from './swag-paypal-method-domain-association.html.twig';
import './swag-paypal-method-domain-association.scss';

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
            hidden: localStorage.getItem('domain-association-hidden') === 'true',
        };
    },

    computed: {
        settingsStore() {
            return Shopware.Store.get('swagPayPalSettings');
        },

        domainAssociationLink() {
            return this.settingsStore.get('SwagPayPal.settings.sandbox')
                ? 'https://www.sandbox.paypal.com/uccservicing/apm/applepay'
                : 'https://www.paypal.com/uccservicing/apm/applepay';
        },

        show() {
            return !this.hidden && this.paymentMethod?.active;
        },
    },

    methods: {
        onCloseAlert() {
            this.hidden = true;
            localStorage.setItem('domain-association-hidden', 'true');
        },
    },
});

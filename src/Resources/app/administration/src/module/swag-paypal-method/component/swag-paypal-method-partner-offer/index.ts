import template from './swag-paypal-method-partner-offer.html.twig';
import './swag-paypal-method-partner-offer.scss';

const HIDDEN_STORAGE_KEY = 'swag-paypal-businesskredit-offer-hidden';

/**
 * The offer runs until the end of 2026-12-31 in the merchant's local time.
 */
const OFFER_END = new Date(2027, 0, 1).getTime();

/**
 * Both the banner copy and the landing page behind it are German only,
 * so the offer is limited to merchants working in a German administration.
 */
const OFFER_LANGUAGE = 'de';

export default Shopware.Component.wrapComponentConfig({
    template,

    data(): {
        hidden: boolean;
    } {
        return {
            hidden: localStorage.getItem(HIDDEN_STORAGE_KEY) === 'true',
        };
    },

    computed: {
        offerLink(): string {
            return 'https://www.shopware.com/de/paypal-businesskredit';
        },

        isOfferRunning(): boolean {
            return Date.now() < OFFER_END;
        },

        isGermanMerchant(): boolean {
            const locale = Shopware.Store.get('session').currentLocale;

            return locale?.split('-')[0] === OFFER_LANGUAGE;
        },

        show(): boolean {
            return !this.hidden && this.isOfferRunning && this.isGermanMerchant;
        },
    },

    methods: {
        onCloseBanner() {
            this.hidden = true;
            localStorage.setItem(HIDDEN_STORAGE_KEY, 'true');
        },
    },
});

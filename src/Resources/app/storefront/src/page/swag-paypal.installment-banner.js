import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

export default class SwagPayPalInstallmentBanner extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * This option holds the merchant id specified in the settings
         *
         * @type string
         */
        merchantPayerId: '',

        /**
         * This option toggles the PayNow/Login text at PayPal
         *
         * @type boolean
         */
        commit: true,

        /**
         * This option holds the buyer country for Pay Later localization
         *
         * @type string
         */
        crossBorderBuyerCountry: undefined,

        /**
         * Amount of money, which will be used to calculate the examples
         *
         * @type number
         */
        amount: 0,

        /**
         * Currency used for the examples
         *
         * @type string
         */
        currency: 'EUR',

        /**
         * Layout of the installment banner
         * Available layouts:
         *  - flex (graphical)
         *  - text
         *
         * @type string
         */
        layout: 'text',

        /**
         * Color of the graphical banner
         * Available colors:
         *  - blue
         *  - black
         *  - white
         *  - gray
         *
         * @type string
         */
        color: 'blue',

        /**
         * Ratio of the graphical banner
         * Available values:
         *  - 1x1
         *  - 20x1
         *  - 8x1
         *  - 1x4
         *
         * @type string
         */
        ratio: '8x1',

        /**
         * Layout type for the text banner
         * Available values:
         *  - primary
         *  - alternative
         *  - inline
         *  - none
         *
         * @type string
         */
        logoType: 'primary',

        /**
         * Text color of the text banner.
         * Available values:
         *  - black
         *  - white
         *
         * @type string
         */
        textColor: 'black',

        /**
         * This option holds the partner attribution id
         *
         * @type string
         */
        partnerAttributionId: '',
    };

    init() {
        this.createInstallmentBanner();
    }

    createInstallmentBanner() {
        this.createScript((paypal) => {
            paypal.Messages(this.getBannerConfig()).render(this.el);
        });
    }

    getBannerConfig() {
        return {
            amount: this.options.amount,
            buyerCountry: this.options.crossBorderBuyerCountry || undefined,
            currency: this.options.currency,
            style: {
                layout: this.options.layout,
                color: this.options.color,
                ratio: this.options.ratio,
                logo: {
                    type: this.options.logoType,
                },
                text: {
                    color: this.options.textColor,
                },
            },
        };
    }
}

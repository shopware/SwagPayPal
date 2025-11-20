import SwagPaypalBase, { SwagPaypalBaseOptions } from '../base/swag-paypal.base';

interface SwagPaypalMessagesOptions extends SwagPaypalBaseOptions {
    crossBorderBuyerCountry?: string;
    amount: number;
    layout: 'flex' | 'text';
    color: 'blue' | 'black' | 'white' | 'gray';
    ratio: '1x1' | '20x1' | '8x1' | '1x4';
    logoType: 'primary' | 'alternative' | 'inline' | 'none';
    textColor: 'black' | 'white';
}

export default class SwagPaypalMessages extends SwagPaypalBase {
    static options: SwagPaypalMessagesOptions = {
        ...this.options,

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

        partOfDomContentLoading: false,
    };

    protected get metadata(): { components: 'paypal-messages'[] } {
        return {
            components: ['paypal-messages'],
        };
    }

    private static messagesInstance: Promise<PayPalCoreJS.Messages.PayPalMessages> | null = null;

    protected async prepare(): Promise<void> {
        SwagPaypalMessages.messagesInstance ??= this.instance!.createPayPalMessages();

        await SwagPaypalMessages.messagesInstance;
    }
}

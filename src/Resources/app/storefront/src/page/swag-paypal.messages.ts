import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';
import { PaypalButtonHelper } from '../helper/paypal-button.helper';

export interface SwagPaypalMessagesOptions extends SwagPaypalBaseOptions, PayPalCoreJS.Messages.ContentOptions {
    paymentMethodId: string;
    crossBorderBuyerCountry: string;
}

export default class SwagPaypalMessages extends SwagPaypalBase {
    declare options: SwagPaypalMessagesOptions;
    declare el: PayPalCoreJS.HTMLPaypalMessage | undefined;

    static options: SwagPaypalMessagesOptions = {
        ...SwagPaypalBase.options,
        amount: '0',
        logoType: 'WORDMARK',
        logoPosition: 'LEFT',
        textColor: 'BLACK',
        paymentMethodId: '',
        crossBorderBuyerCountry: '',
    };

    protected get metadata(): { components: 'paypal-messages'[] } {
        return {
            components: ['paypal-messages'],
        };
    }

    private static messagesInstance: Promise<PayPalCoreJS.Messages.PayPalMessages> | null = null;

    protected async prepare(): Promise<void> {
        if (this.options.crossBorderBuyerCountry) {
            this.el!.buyerCountry = this.options.crossBorderBuyerCountry;
        }

        this.el!.currencyCode = this.options.currency;
        this.el!.logoType = this.options.logoType;
        this.el!.logoPosition = this.options.logoPosition;
        this.el!.textColor = this.options.textColor;
        this.el!.amount = String(this.options.amount);

        SwagPaypalMessages.messagesInstance ??= this.instance!.createPayPalMessages();
        const messagesInstance = await SwagPaypalMessages.messagesInstance;

        const [_, learnMore] = await Promise.all([
            messagesInstance.fetchContent(this.el!.getFetchContentOptions()),
            messagesInstance.createLearnMore({ presentationMode: 'AUTO' }),
        ]);

        this.el!.addEventListener('paypal-message-click', (event) => {
            event.preventDefault();
            learnMore.open(event.detail.config);
        });
    }

    protected afterPrepare(): void {
        PaypalButtonHelper.enable(this.el!);
    }
}

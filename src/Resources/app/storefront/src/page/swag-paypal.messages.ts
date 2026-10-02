import SwagPaypalBase, { type SwagPaypalBaseOptions } from '../base/swag-paypal.base';
import { ElementHelper } from '../helper/element.helper';

export interface SwagPaypalMessagesOptions extends SwagPaypalBaseOptions, PayPalCoreJS.Messages.ContentOptions {
    paymentMethodId: string;
    crossBorderBuyerCountry: string;
    textSize: 10 | 12 | 16;
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
        textSize: 12,
    };

    protected get metadata(): { components: 'paypal-messages'[] } {
        return {
            components: ['paypal-messages'],
        };
    }

    private static messagesInstance: PayPalCoreJS.Messages.PayPalMessages | null = null;

    protected async setup(): Promise<void> {
        if (this.options.crossBorderBuyerCountry) {
            this.el!.buyerCountry = this.options.crossBorderBuyerCountry;
        }

        this.el!.currencyCode = this.options.currency;
        this.el!.logoType = this.options.logoType;
        this.el!.logoPosition = this.options.logoPosition;
        this.el!.textColor = this.options.textColor;
        this.el!.amount = String(this.options.amount);

        SwagPaypalMessages.messagesInstance ??= this.instance!.createPayPalMessages();

        await SwagPaypalMessages.messagesInstance.fetchContent(this.el!.getFetchContentOptions());
        const learnMore = SwagPaypalMessages.messagesInstance.createLearnMore({ presentationMode: 'AUTO' });

        this.el!.addEventListener('paypal-message-click', (event) => {
            event.preventDefault();
            learnMore.open(event.detail.config);
        });

        if (typeof this.options.textSize === 'number' && this.options.textSize > 0) {
            this.el!.style.setProperty('--paypal-message-font-size', `${this.options.textSize}px`);
        }
    }

    protected afterSetup(): void {
        ElementHelper.enable(this.el!);
    }
}

import SwagPaypalBase from '../base/swag-paypal.base';

export default class SwagPaypalMessages extends SwagPaypalBase {
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

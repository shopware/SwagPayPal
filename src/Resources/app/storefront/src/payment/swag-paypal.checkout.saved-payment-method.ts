import type { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
import SwagPaypalPayment, { type SwagPaypalPaymentOptions } from '../base/swag-paypal.payment';
import PayPalPluginError from '../base/paypal-plugin.error';
import { RequestHelper } from '../helper/request.helper';

export interface SwagPaypalCheckoutSavedPaymentMethodOptions extends SwagPaypalPaymentOptions {
    orderId: string | null;
    createOrderUrl: string;
    editUrl: string;
    identifierSelector: string;
    previewSelector: string;
    editButtonSelector: string;
    approvedOrderSelector: string;
    approvedOrderIdSelector: string;
}

/**
 * Lets a buyer with a saved PayPal wallet change its funding instrument for one order.
 * The buyer pays with the regular order button: with the order they approved in PayPal's window, if any,
 * or else with the saved wallet itself, which needs no JavaScript at all.
 * Therefore, errors only keep the change option hidden, and never block the checkout.
 */
export default class SwagPaypalCheckoutSavedPaymentMethod extends SwagPaypalPayment<'paypal'> {
    declare options: SwagPaypalCheckoutSavedPaymentMethodOptions;

    static options: SwagPaypalCheckoutSavedPaymentMethodOptions = {
        ...SwagPaypalPayment.options,

        pageType: 'checkout' as const,

        /**
         * Is set, if the plugin is used on the order edit page
         */
        orderId: null,

        /**
         * URL to create a new PayPal order
         */
        createOrderUrl: '',

        /**
         * URL to remember the PayPal order the buyer approved with another funding instrument
         */
        editUrl: '',

        /**
         * Selector of the saved wallet's identifier, shown until PayPal's preview is ready
         */
        identifierSelector: '.swag-paypal-saved-payment-method-identifier',

        /**
         * Selector of PayPal's preview of the funding instrument
         */
        previewSelector: 'paypal-saved-payment-methods',

        /**
         * Selector of the button opening PayPal's window to change the funding instrument
         */
        editButtonSelector: '.swag-paypal-saved-payment-method-edit',

        /**
         * Selector of the note, that the buyer changed the funding instrument for this order
         */
        approvedOrderSelector: '.swag-paypal-saved-payment-method-approved',

        /**
         * Selector of the confirm form's input, which takes the PayPal order the buyer approved
         */
        approvedOrderIdSelector: '.swag-paypal-saved-payment-method-order-id',
    };

    private session: PayPalCoreJS.SavedPaymentMethods.Session | null = null;

    protected get metadata(): { components: 'paypal-saved-payment-methods'[]; fundingSource: 'paypal' } {
        return {
            components: ['paypal-saved-payment-methods'],
            fundingSource: 'paypal',
        };
    }

    /**
     * Checks eligibility with this plugin's SDK instance, which is bound to the saved wallet,
     * instead of the result shared by all PayPal plugins on the page
     */
    protected async checkFundingEligiblity(fundingSource: PayPalCoreJS.FundingSource = this.metadata.fundingSource): Promise<void> {
        const eligibleMethods = await this.instance!.findEligibleMethods({ currencyCode: this.options.currency });

        if (!eligibleMethods.isEligible(fundingSource) || !eligibleMethods.getDetails(this.metadata.fundingSource).canBeVaulted) {
            throw PayPalPluginError.notEligible(fundingSource);
        }
    }

    protected setup(): void {
        this.session = this.instance!.createPayPalEditSavedPaymentSession({
            commit: false,
            onApprove: this.onApprove.bind(this),
            onCancel: this.onCancel.bind(this),
            onError: this.onError.bind(this),
        });

        this.getElement(this.options.previewSelector).addEventListener('click', this.startEdit.bind(this));
        this.getElement(this.options.editButtonSelector).addEventListener('click', this.startEdit.bind(this));
    }

    protected afterSetup(): void {
        this.getElement(this.options.identifierSelector).hidden = true;
        this.getElement(this.options.previewSelector).hidden = false;
        this.getElement(this.options.editButtonSelector).hidden = false;
    }

    /**
     * PayPal only shows a saved wallet with a client token bound to it
     */
    protected async _fetchCredentials(): Promise<{ clientToken: string }> {
        const response = await RequestHelper.fetch(this.options.clientTokenUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vault: true }),
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('client-token', response);
        }

        const { token } = await response.json() as { token: string };
        return { clientToken: token };
    }

    protected async onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        try {
            const response = await RequestHelper.fetch(this.options.editUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ paypalOrderId: orderId, orderId: this.options.orderId }),
            });

            if (!response.ok) {
                throw await PayPalPluginError.api('saved-payment-method-edit', response);
            }
        } catch (error) {
            // the confirm form still takes the order, it is only lost on reload
            console.error(this._pluginName, error);
        }

        const approvedOrderId = this.getElement<HTMLInputElement>(this.options.approvedOrderIdSelector);
        approvedOrderId.value = orderId;
        approvedOrderId.disabled = false;

        this.getElement(this.options.approvedOrderSelector).hidden = false;
    }

    protected onCancel(): void {
        // the buyer keeps the funding instrument they had before
    }

    /**
     * Only logs, as the order button still pays with the saved wallet
     */
    protected handleError(error: PayPalPluginError): Promise<void> {
        console.error(this._pluginName, error);

        return Promise.resolve();
    }

    private startEdit(event: Event): void {
        event.preventDefault();

        // the order must not be awaited before starting the session, or the browser blocks PayPal's window
        this.session?.start({ presentationMode: 'auto' }, this.createOrder())
            .catch((error: unknown) => this.handleError(PayPalPluginError.submitFlow(PayPalPluginError.CODE_GENERIC, error)));
    }

    private async createOrder(): Promise<{ orderId: string }> {
        const formData = new FormData();
        if (this.options.orderId !== null) {
            formData.set('orderId', this.options.orderId);
        }

        const response = await RequestHelper.fetch(this.options.createOrderUrl, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('create-order', response);
        }

        const { token } = await response.json() as { token: string };
        return { orderId: token };
    }

    private getElement<E extends HTMLElement = HTMLElement>(selector: string): E {
        const element = this.el?.querySelector<E>(selector);

        if (!element) {
            throw PayPalPluginError.create(PayPalPluginError.CODE_SCRIPT, null, `Element not found with selector: ${selector}`);
        }

        return element;
    }
}

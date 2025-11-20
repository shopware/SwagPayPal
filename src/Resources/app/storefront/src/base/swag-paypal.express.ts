import { PaypalButtonHelper } from '../helper/paypal-button.helper';
import SwagPaypalPayment, { SwagPaypalPaymentOptions } from './swag-paypal.payment';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
import PayPalPluginError from './paypal-plugin.error';

export interface SwagPaypalExpressOptions extends SwagPaypalPaymentOptions {
    buttonColor: 'gold' | 'blue' | 'silver' | 'black' | 'white';
    disabledClass: string;
    buyButtonSelector: string;
    tagline: boolean;
    addProductToCart: boolean;
    contextSwitchUrl: string;
    payPalPaymentMethodId: string;
    deleteCartUrl: string;
    prepareCheckoutUrl: string;
    checkoutConfirmUrl: string;
    cancelRedirectUrl: string;
    useAlternativePaymentMethods: boolean;
    commit: boolean;
    scriptAwaitVisibility: boolean;
    partOfDomContentLoading: boolean;
}

export default abstract class SwagPaypalExpress<FS extends PayPalCoreJS.FundingSource> extends SwagPaypalPayment<FS> {
    static options: SwagPaypalExpressOptions = {
        ...this.options,

        /**
         * This option defines the selector for the buy button on the product detail page and listing.
         */
        buyButtonSelector: '.btn-buy',

        /**
         * This option toggles the text below the PayPal Express button
         */
        tagline: false,

        /**
         * This option toggles the Process whether or not the product needs to be added to the cart.
         */
        addProductToCart: false,

        /**
         * URL to set payment method to PayPal
         */
        contextSwitchUrl: '',

        payPalPaymentMethodId: '',

        /**
         * URL to delete an existing cart in Shopware
         */
        deleteCartUrl: '',

        /**
         * URL for creating and logging in guest customer
         */
        prepareCheckoutUrl: '',

        /**
         * URL to the checkout confirm page
         */
        checkoutConfirmUrl: '',

        /**
         * URL for redirecting to after user cancels
         */
        cancelRedirectUrl: '',

        partOfDomContentLoading: false,
    };

    GENERIC_ERROR = 'SWAG_PAYPAL__EXPRESS_GENERIC_ERROR';
    USER_CANCELLED = 'SWAG_PAYPAL__EXPRESS_USER_CANCELLED';

    get buyButtonForm(): HTMLFormElement|null {
        const form = this.el?.closest('form');
        return form instanceof HTMLFormElement ? form : null;
    }

    get buyButton(): HTMLButtonElement|null {
        return this.buyButtonForm?.querySelector<'button'>(this.options.buyButtonSelector) || null;
    }

    protected async afterPrepare(): Promise<void> {
        if (this.options.addProductToCart && this.buyButton) {
            if (this.buyButton?.disabled) {
                PaypalButtonHelper.disable(this.el!);
            }

            const observer = new MutationObserver(this.buyButtonObserver.bind(this));
            observer.observe(this.buyButton, { attributes: true });
        }

        PaypalButtonHelper.enable(this.el!);
    }

    protected buyButtonObserver(mutations: MutationRecord[]): void {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'disabled') {
                if (mutation.target.disabled) {
                    PaypalButtonHelper.disable(this.el!);
                } else {
                    PaypalButtonHelper.enable(this.el!);
                }
            }
        });
    }

    protected async createOrder(): Promise<{ orderId: string }> {
        const contextResponse = await fetch(this.options.contextSwitchUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                paymentMethodId: this.options.payPalPaymentMethodId,
                deleteCart: this.options.addProductToCart,
            }),
        });

        if (!contextResponse.ok) {
            throw new Error(`Failed to switch payment method (${contextResponse.status}): ${await contextResponse.text()}`);
        }

        if (this.options.addProductToCart) {
            await this.addProductToCart();
        }

        const orderResponse = await fetch(this.options.createOrderUrl, {
            method: 'POST',
            body: new FormData(),
        });

        if (!orderResponse.ok) {
            throw new Error(`Failed to create order (${orderResponse.status}): ${await orderResponse.text()}`);
        }

        return { orderId: (await orderResponse.json()).token };
    }

    protected addProductToCart(): Promise<void> {
        const plugin = window.PluginManager.getPluginInstanceFromElement(this.buyButtonForm!, 'AddToCart');

        return new Promise(resolve => {
            plugin.$emitter.subscribe('openOffCanvasCart', () => resolve());
            this.buyButton!.click();
        });
    }

    protected async onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        PageLoadingIndicatorUtil.create();

        const response = await fetch(this.options.prepareCheckoutUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: orderId }),
        });

        if (!response.ok) {
            throw new Error(`Failed to validate order (${response.status}): ${await response.text()}`);
        }

        window.location.replace(this.options.checkoutConfirmUrl);
    }

    protected handleError(code: string, fatal?: boolean, error?: unknown): Promise<void> {
        if (error instanceof PayPalPluginError && error.code === PayPalPluginError.NOT_ELIGIBLE) {
            PaypalButtonHelper.disable(this.el!);
            return Promise.resolve();
        }

        return super.handleError(code, fatal, error);
    }

    protected onErrorHandled(error: PayPalPluginError) {
        if (error.code === this.USER_CANCELLED) {
            window.scrollTo(0, 0);
            window.location = this.options.cancelRedirectUrl;
        }
    }
}

import { ElementHelper } from '../helper/element.helper';
import type { SwagPaypalPaymentOptions } from './swag-paypal.payment';
import SwagPaypalPayment from './swag-paypal.payment';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import type { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
import PayPalPluginError from './paypal-plugin.error';
import { RequestHelper } from '../helper/request.helper';

export interface SwagPaypalExpressOptions extends SwagPaypalPaymentOptions {
    buyButtonSelector: string;
    addProductToCart: boolean;
    contextSwitchUrl: string;
    createOrderUrl: string;
    prepareCheckoutUrl: string;
    checkoutConfirmUrl: string;
    cancelRedirectUrl: string;
    payPalPaymentMethodId: string;
}

export default abstract class SwagPaypalExpress<FS extends PayPalCoreJS.FundingSource> extends SwagPaypalPayment<FS> {
    declare options: SwagPaypalExpressOptions;
    static options: SwagPaypalExpressOptions = {
        ...SwagPaypalPayment.options,

        /**
         * This option defines the selector for the buy button on the product detail page and listing.
         */
        buyButtonSelector: '.btn-buy',

        /**
         * This option toggles the Process whether or not the product needs to be added to the cart.
         */
        addProductToCart: false,

        /**
         * URL to set payment method to PayPal
         */
        contextSwitchUrl: '',

        /**
         * URL to create a new PayPal order
         */
        createOrderUrl: '',

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

        payPalPaymentMethodId: '',
    };

    get buyButtonForm(): HTMLFormElement|null {
        const form = this.el?.closest('form');
        return form instanceof HTMLFormElement ? form : null;
    }

    get buyButton(): HTMLButtonElement|null {
        return this.buyButtonForm?.querySelector<HTMLButtonElement>(this.options.buyButtonSelector) || null;
    }

    protected afterSetup(): void {
        if (this.options.addProductToCart && this.buyButton) {
            if (this.buyButton?.disabled) {
                ElementHelper.disable(this.el!);
            } else {
                ElementHelper.enable(this.el!);
            }

            const observer = new MutationObserver(this.buyButtonObserver.bind(this));
            observer.observe(this.buyButton, { attributes: true });
        } else {
            ElementHelper.enable(this.el!);
        }
    }

    protected buyButtonObserver(mutations: MutationRecord[]): void {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'disabled') {
                if ((mutation.target as HTMLButtonElement).disabled) {
                    ElementHelper.disable(this.el!);
                } else {
                    ElementHelper.enable(this.el!);
                }
            }
        });
    }

    protected async createOrder(): Promise<{ orderId: string }> {
        const contextResponse = await RequestHelper.fetch(this.options.contextSwitchUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                paymentMethodId: this.options.payPalPaymentMethodId,
                deleteCart: this.options.addProductToCart,
            }),
        });

        if (!contextResponse.ok) {
            throw await PayPalPluginError.api('context-switch', contextResponse);
        }

        if (this.options.addProductToCart) {
            await this.addProductToCart();
        }

        const orderResponse = await RequestHelper.fetch(this.options.createOrderUrl, {
            method: 'POST',
            body: new FormData(),
        });

        if (!orderResponse.ok) {
            throw await PayPalPluginError.api('create-order', orderResponse);
        }

        const { token } = await orderResponse.json() as { token: string };
        return { orderId: token };
    }

    protected addProductToCart(): Promise<void> {
        const plugin = window.PluginManager.getPluginInstanceFromElement(this.buyButtonForm!, 'AddToCart') as SwPlugin;

        return new Promise(resolve => {
            plugin.$emitter!.subscribe('openOffCanvasCart', () => resolve());
            this.buyButton!.click();
        });
    }

    protected async onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        PageLoadingIndicatorUtil.create();

        const response = await RequestHelper.fetch(this.options.prepareCheckoutUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: orderId }),
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('prepare-checkout', response);
        }

        window.location.replace(this.options.checkoutConfirmUrl);
    }

    protected handleError(error: PayPalPluginError): Promise<void> {
        if (error.code === PayPalPluginError.CODE_NOT_ELIGIBLE) {
            ElementHelper.hide(this.el!);
            return Promise.resolve();
        }

        if (['SWAG_PAYPAL__USER_CANCELLED', 'SWAG_PAYPAL__GENERIC_ERROR'].includes(error.code)) {
            error.code = error.code.replace('SWAG_PAYPAL__', 'SWAG_PAYPAL__EXPRESS_');
        }

        return super.handleError(error);
    }

    protected afterHandleError(error: PayPalPluginError) {
        if (error.code.includes('USER_CANCELLED')) {
            window.scrollTo(0, 0);
            window.location.href = this.options.cancelRedirectUrl;
        } else if (error.step === PayPalPluginError.STEP_SUBMIT_FLOW) {
            super.afterHandleError(error);
        }
    }
}

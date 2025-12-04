import type { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
import type { SubmissionData, SwagPaypalPaymentOptions } from './swag-paypal.payment';
import SwagPaypalPayment from './swag-paypal.payment';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import PayPalPluginError from './paypal-plugin.error';
import { PaypalButtonHelper } from '../helper/paypal-button.helper';

export interface SwagPaypalCheckoutOptions extends SwagPaypalPaymentOptions {
    orderId: string|null;
    confirmOrderFormSelector: string;
    confirmOrderButtonSelector: string;
    createOrderUrl: string;
    preventErrorReload: boolean;
}

export default abstract class SwagPaypalCheckout<FS extends PayPalCoreJS.FundingSource> extends SwagPaypalPayment<FS> {
    declare options: SwagPaypalCheckoutOptions;

    static options: SwagPaypalCheckoutOptions = {
        ...SwagPaypalPayment.options,

        pageType: 'checkout' as const,

        /**
         * Is set, if the plugin is used on the order edit page
         */
        orderId: null,

        /**
         * Selector of the order confirm form
         */
        confirmOrderFormSelector: '#confirmOrderForm',

        /**
         * Selector of the submit button of the order confirm form
         */
        confirmOrderButtonSelector: 'button[type="submit"]',

        /**
         * URL to create a new PayPal order
         */
        createOrderUrl: '',

        /**
         * If set to true, the payment method caused an error and already reloaded the page.
         * This could for example happen if the funding type is not eligible.
         */
        preventErrorReload: false,
    };

    protected abstract get metadata(): { components: PayPalCoreJS.Components[]; fundingSource: FS; product: Products };

    protected get confirmOrderForm(): HTMLFormElement {
        const form = document.querySelector<HTMLFormElement>(this.options.confirmOrderFormSelector);

        if (!(form instanceof HTMLFormElement)) {
            throw PayPalPluginError.create(PayPalPluginError.CODE_SCRIPT, null, `Confirm order form not found with selector: ${this.options.confirmOrderFormSelector}`);
        }

        return form;
    }

    protected get confirmOrderButton(): HTMLButtonElement {
        const button = this.confirmOrderForm.querySelector(this.options.confirmOrderButtonSelector);

        if (!(button instanceof HTMLButtonElement)) {
            throw PayPalPluginError.create(PayPalPluginError.CODE_SCRIPT, null, `Confirm order button not found with selector: ${this.options.confirmOrderButtonSelector}`);
        }

        return button;
    }

    init() {
        if (this.options.preventErrorReload) {
            this.confirmOrderButton.disabled = true;

            return;
        }

        super.init();
    }

    protected async beforeSetup(): Promise<void> {
        PaypalButtonHelper.load(this.confirmOrderButton);

        return super.beforeSetup();
    }

    protected async afterSetup(): Promise<void> {
        PaypalButtonHelper.hide(this.confirmOrderButton);
        PaypalButtonHelper.enable(this.el!);

        return super.afterSetup();
    }

    protected submitValidation(data: SubmissionData<FS>): void {
        if (!this.confirmOrderForm.reportValidity()) {
            throw new Error('Form is invalid');
        }
    }

    protected async createOrder(): Promise<{ orderId: string; vaultSetupToken?: string }> {
        const formData = new FormData(this.confirmOrderForm);
        formData.set('product', this.metadata.product);

        const orderId = this.options.orderId;
        if (orderId !== null) {
            formData.set('orderId', orderId);
        }

        const response = await fetch(this.options.createOrderUrl, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw await PayPalPluginError.api('create-order', response);
        }

        const { token } = await response.json() as { token: string };
        return { orderId: token };
    }

    protected onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        const existingInput = this.confirmOrderForm.querySelector('input[name="paypalOrderId"]');
        if (existingInput) {
            return Promise.resolve();
        }

        PageLoadingIndicatorUtil.create();

        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'paypalOrderId');
        input.setAttribute('value', orderId);

        this.confirmOrderForm.appendChild(input);
        this.confirmOrderForm.submit();

        return Promise.resolve();
    }
}

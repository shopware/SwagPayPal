import { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
import SwagPaypalPayment, { SubmissionData, SwagPaypalPaymentOptions } from './swag-paypal.payment';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import PayPalPluginError from './paypal-plugin.error';
import { PaypalButtonHelper } from '../helper/paypal-button.helper';

export interface SwagPaypalCheckoutOptions extends SwagPaypalPaymentOptions {
    orderId: string|null;
    confirmOrderFormSelector: string;
    confirmOrderButtonSelector: string;
    preventErrorReload: boolean;
}

export default abstract class SwagPaypalCheckout<FS extends PayPalCoreJS.FundingSource> extends SwagPaypalPayment<FS> {
    static options: SwagPaypalCheckoutOptions = {
        ...this.options,

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
         * If set to true, the payment method caused an error and already reloaded the page.
         * This could for example happen if the funding type is not eligible.
         */
        preventErrorReload: false,
    }

    protected get confirmOrderForm(): HTMLFormElement {
        const form = document.querySelector<'form'>(this.options.confirmOrderFormSelector);

        if (!(form instanceof HTMLFormElement)) {
            throw PayPalPluginError.scriptError(`Confirm order form not found with selector: ${this.options.confirmOrderFormSelector}`);
        }

        return form;
    }

    protected get confirmOrderButton(): HTMLButtonElement {
        const button = this.confirmOrderForm.querySelector(this.options.confirmOrderButtonSelector);

        if (!(button instanceof HTMLButtonElement)) {
            throw PayPalPluginError.scriptError(`Confirm order button not found with selector: ${this.options.confirmOrderButtonSelector}`);
        }

        return button;
    }

    protected get product(): Products {
        return 'default' as const;
    }

    async init() {
        if (this.options.preventErrorReload) {
            this.confirmOrderButton.disabled = true;

            return;
        }

        return super.init();
    }

    protected async beforePrepare(): Promise<void> {
        PaypalButtonHelper.load(this.confirmOrderButton);

        return super.beforePrepare();
    }

    protected async afterPrepare(): Promise<void> {
        PaypalButtonHelper.hide(this.confirmOrderButton);
        PaypalButtonHelper.enable(this.el!);

        return super.afterPrepare();
    };

    protected beforeSubmit(data: SubmissionData<FS>): void {
        if (!this.confirmOrderForm?.reportValidity()) {
            throw new Error('Form is invalid');
        }
    }

    protected async createOrder(): Promise<{ orderId: string, vaultSetupToken?: string }> {
        const formData = FormSerializeUtil.serialize(this.confirmOrderForm);
        formData.set('product', this.product);

        const orderId = this.options.orderId;
        if (orderId !== null) {
            formData.set('orderId', orderId);
        }

        const response = await fetch(this.options.createOrderUrl, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Failed to create order(${response.status}): ${await response.text()}`);
        }

        return { orderId: (await response.json()).token };
    }

    protected async onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        const existingInput = this.confirmOrderForm?.querySelector('input[name="paypalOrderId"]');
        if (existingInput) {
            return;
        }

        PageLoadingIndicatorUtil.create();

        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'paypalOrderId');
        input.setAttribute('value', orderId);

        this.confirmOrderForm?.appendChild(input);
        this.confirmOrderForm?.submit();

        return;
    }
}

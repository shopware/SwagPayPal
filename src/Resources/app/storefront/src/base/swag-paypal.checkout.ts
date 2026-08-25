import type { OnApproveDataOneTimePayments, OneTimePaymentSession } from '@paypal/paypal-js/sdk-v6';
import type { SubmissionData, SwagPaypalPaymentOptions } from './swag-paypal.payment';
import SwagPaypalPayment from './swag-paypal.payment';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import PayPalPluginError from './paypal-plugin.error';
import { ElementHelper } from '../helper/element.helper';
import { RequestHelper } from '../helper/request.helper';

export interface SwagPaypalCheckoutOptions extends SwagPaypalPaymentOptions {
    orderId: string|null;
    confirmOrderFormSelector: string;
    confirmOrderButtonSelector: string;
    createOrderUrl: string;
    preventErrorReload: boolean;
    appSwitchEnabled: boolean;
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

        appSwitchEnabled: false,
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
        ElementHelper.load(this.confirmOrderButton);

        await super.beforeSetup();
    }

    protected afterSetup(): void {
        ElementHelper.hide(this.confirmOrderButton);
        ElementHelper.enable(this.el!);
    }

    protected submitValidation(data: SubmissionData<FS>): void {
        if (!this.confirmOrderForm.checkValidity()) {
            this.focusFirstInvalidField();
            throw new Error('Form is invalid');
        }
    }

    protected async createOrder(): Promise<{ orderId: string }> {
        const formData = new FormData(this.confirmOrderForm);
        formData.set('product', this.metadata.product);

        const orderId = this.options.orderId;
        if (orderId !== null) {
            formData.set('orderId', orderId);
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

    protected onApprove({ orderId }: OnApproveDataOneTimePayments): Promise<void> {
        const existingInput = this.confirmOrderForm.elements.namedItem('paypalOrderId');
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

    protected async tryStartWithAppSwitch({ paymentSession }: { paymentSession: OneTimePaymentSession }): Promise<void> {
        const createOrder = this.createOrder();
        const presentationMode = this.options.appSwitchEnabled ? 'direct-app-switch' : 'auto';

        try {
            await paymentSession.start({ presentationMode }, createOrder);
        } catch (error: unknown) {
            if (error instanceof Error && 'isRecoverable' in error && error.isRecoverable === true && this.options.appSwitchEnabled) {
                await paymentSession.start({ presentationMode: 'auto' }, createOrder);
            }

            throw error;
        }
    }

    protected async resumeAppSwitch({ paymentSession }: { paymentSession: OneTimePaymentSession }): Promise<void> {
        ElementHelper.disable(this.confirmOrderButton);
        PageLoadingIndicatorUtil.create();

        try {
            await paymentSession.resume?.();
        } catch (error) {
            // wrap as submit flow, though called in setup
            throw PayPalPluginError.submitFlow(PayPalPluginError.CODE_GENERIC, error);
        }
    }

    private focusFirstInvalidField(): void {
        const fields = Array.from(this.confirmOrderForm.elements) as HTMLElement[];
        const field = fields.find((element) => element.matches?.(':invalid:not(fieldset)'));

        if (!field) {
            return;
        }

        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus();
    }
}

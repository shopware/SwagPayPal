import SwagPaypalBase from './swag-paypal.base';
import type { SwagPaypalBaseOptions } from './swag-paypal.base';
import { PaypalButtonHelper } from '../helper/paypal-button.helper';
import PayPalPluginError from './paypal-plugin.error';

export interface SwagPaypalPaymentOptions extends SwagPaypalBaseOptions {
}

export interface SubmissionData<FS extends PayPalCoreJS.FundingSource> {
    paymentSession: PayPalCoreJS.PaymentSession<FS>;
    [key: string]: unknown;
}

export default abstract class SwagPaypalPayment<FS extends PayPalCoreJS.FundingSource> extends SwagPaypalBase {
    declare options: SwagPaypalPaymentOptions;
    static options: SwagPaypalPaymentOptions = {
        ...SwagPaypalBase.options,
    };

    protected abstract get metadata(): { components: PayPalCoreJS.Components[]; fundingSource: FS };

    protected async checkFundingEligiblity(fundingSource: PayPalCoreJS.FundingSource = this.metadata.fundingSource): Promise<void> {
        const eligibleMethods = await this.findEligibleMethods();

        if (!eligibleMethods.isEligible(fundingSource)) {
            throw PayPalPluginError.notEligible(fundingSource);
        }
    }

    protected async getFundingDetails(): Promise<PayPalCoreJS.FindEligibleMethods.Details<FS>> {
        const eligibleMethods = await this.findEligibleMethods();
        return eligibleMethods.getDetails(this.metadata.fundingSource);
    }

    protected async beforeSetup(): Promise<void> {
        await super.beforeSetup();
        await this.checkFundingEligiblity();
    }

    protected async submissionFlow(data: SubmissionData<FS>): Promise<void> {
        try {
            this.submitValidation(data);
        } catch {
            return;
        }

        try {
            await this.submit(data);
            await this.afterSubmit(data);
        } catch (error) {
            await this.handleError(PayPalPluginError.submitFlow(PayPalPluginError.CODE_GENERIC, error));
        }
    }

    /**
     * Validate submission. Any error will silently stop the submission flow.
     */
    protected submitValidation(data: SubmissionData<FS>): void {}

    protected submit(data: SubmissionData<FS>): void|Promise<void> {}

    protected afterSubmit(data: SubmissionData<FS>): void|Promise<void> {}

    protected async onApprove(data: { orderId: string }): Promise<void> {}

    /**
     * Stop payment process with a generic error.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param error - Can be any type, but will be converted to a string
     */
    protected onError(error: unknown = undefined): void {
        this.handleError(PayPalPluginError.submitFlow(PayPalPluginError.CODE_GENERIC, error));
    }

    /**
     * Cancel the payment process with a generic cancellation.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param error - Can be any type, but will be converted to a string
     */
    protected onCancel(error: unknown = undefined): void {
        this.handleError(PayPalPluginError.submitFlow(PayPalPluginError.CODE_USER_CANCELLED, error));
    }

    protected async handleError(error: PayPalPluginError): Promise<void> {
        PaypalButtonHelper.disable(this.el!);

        await super.handleError(error);
    }
}

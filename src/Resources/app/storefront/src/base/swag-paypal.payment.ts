import SwagPaypalBase from './swag-paypal.base';
import type { SwagPaypalBaseOptions } from './swag-paypal.base';
import type { OnApproveDataOneTimePayments } from '@paypal/paypal-js/sdk-v6';
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

    protected USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED';
    protected GENERIC_ERROR = 'SWAG_PAYPAL__GENERIC_ERROR';

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

    protected async beforePrepare(): Promise<void> {
        await super.beforePrepare();
        await this.checkFundingEligiblity();
    }

    protected async submissionFlow(data: SubmissionData<FS>): Promise<void> {
        try {
            this.beforeSubmit(data);
        } catch {
            return;
        }

        try {
            await this.submit(data);
            await this.afterSubmit(data);
        } catch (error) {
            await this.handleError(this.GENERIC_ERROR, false, error);
        }
    }

    /**
     * Validate submission. Any error will silently stop the submission flow.
     */
    protected beforeSubmit(data: SubmissionData<FS>): void {}

    protected submit(data: SubmissionData<FS>): void|Promise<void> {}

    protected afterSubmit(data: SubmissionData<FS>): void|Promise<void> {}

    protected onApprove(data: OnApproveDataOneTimePayments): void|Promise<void> {}

    /**
     * Stop payment process with a generic error.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param error - Can be any type, but will be converted to a string
     */
    protected onError(error: unknown = undefined): void {
        this.handleError(this.GENERIC_ERROR, false, error);
    }

    /**
     * Cancel the payment process with a generic cancellation.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param error - Can be any type, but will be converted to a string
     */
    protected onCancel(error: unknown = undefined): void {
        this.handleError(this.USER_CANCELLED, false, error);
    }

    protected async handleError(code: string, fatal: boolean = false, error: unknown = undefined) {
        PaypalButtonHelper.disable(this.el!);

        await super.handleError(code, fatal, error);
    }
}

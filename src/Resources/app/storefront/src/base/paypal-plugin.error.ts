
export default class PayPalPluginError extends Error {
    public readonly code: string;
    public readonly isFatal: boolean;

    public static GENERIC_ERROR = 'SWAG_PAYPAL__GENERIC_ERROR';
    public static NOT_ELIGIBLE = 'SWAG_PAYPAL__NOT_ELIGIBLE';
    public static USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED';
    public static BROWSER_UNSUPPORTED = 'SWAG_PAYPAL__BROWSER_UNSUPPORTED';
    public static SCRIPT_ERROR = 'SWAG_PAYPAL__SCRIPT_ERROR';
    public static SCRIPT_NOT_LOADED = 'SWAG_PAYPAL__SCRIPT_NOT_LOADED';

    protected constructor(message: string, code: string, isFatal: boolean = false) {
        super(message);
        this.name = 'SwagPayPalError';
        this.isFatal = isFatal;
        this.code = code;
    }

    public static create(code: string, fatal: boolean = false, error: unknown = undefined): PayPalPluginError {
        if (error instanceof PayPalPluginError) {
            return error;
        }

        if (error instanceof Error) {
            error = String(error);
        }

        if (error && typeof error !== 'string') {
            error = JSON.stringify(error);
        }

        const errorCode = this.extractErrorCode(error as string);
        if (errorCode) {
            code = errorCode;
        }

        // eslint-disable-next-line
        const message = `PayPal ${fatal ? 'fatal ' : ''}error occurred: ${code} - ${String(error ?? '')}`;

        return new PayPalPluginError(message, code, fatal);
    }

    public static notEligible(fundingSource: PayPalCoreJS.FundingSource): PayPalPluginError {
        return this.create(this.NOT_ELIGIBLE, true, `Funding for "${fundingSource}" is not eligible`);
    }

    public static userCancelled(error: unknown = undefined): PayPalPluginError {
        return this.create(this.USER_CANCELLED, false, error);
    }

    public static browserUnsupported(fundingSource: PayPalCoreJS.FundingSource): PayPalPluginError {
        return this.create(this.BROWSER_UNSUPPORTED, true, `Browser does not support by "${fundingSource}"`);
    }

    public static genericError(fatal: boolean, error: unknown = undefined): PayPalPluginError {
        return this.create(this.GENERIC_ERROR, fatal, error);
    }

    public static scriptError(error: unknown = undefined): PayPalPluginError {
        return this.create(this.SCRIPT_ERROR, true, error);
    }

    public static scriptNotLoaded(fundingSource: PayPalCoreJS.FundingSource): PayPalPluginError {
        return this.create(this.SCRIPT_NOT_LOADED, true, `Script for "${fundingSource}" wasn't load`);
    }

    private static extractErrorCode(error: string): string | null {
        try {
            // eslint-disable-next-line
            const errors = JSON.parse(error)?.errors;

            if (!Array.isArray(errors)) {
                return null;
            }

            // eslint-disable-next-line
            for (const error of errors) {
                if (typeof error !== 'object') {
                    continue;
                }

                // eslint-disable-next-line
                if (typeof error.code === 'string') {
                    // eslint-disable-next-line
                    return error.code;
                }
            }
        } finally {
            return null;
        }
    }
}

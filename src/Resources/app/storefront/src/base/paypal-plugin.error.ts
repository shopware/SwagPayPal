export default class PayPalPluginError extends Error {
    private _message: string;
    private _code: string;
    private _step: string | null;

    public static STEP_SCRIPT_LOAD = 'SCRIPT_LOAD' as const;
    public static STEP_SETUP_FLOW = 'SETUP_FLOW' as const;
    public static STEP_SUBMIT_FLOW = 'SUBMIT_FLOW' as const;
    protected static FATAL_STEPS: string[] = [
        PayPalPluginError.STEP_SCRIPT_LOAD,
        PayPalPluginError.STEP_SETUP_FLOW,
    ];

    public static CODE_GENERIC = 'SWAG_PAYPAL__GENERIC_ERROR' as const;
    public static CODE_SCRIPT = 'SWAG_PAYPAL__SCRIPT_ERROR' as const;
    public static CODE_NOT_ELIGIBLE = 'SWAG_PAYPAL__NOT_ELIGIBLE' as const;
    public static CODE_BROWSER_UNSUPPORTED = 'SWAG_PAYPAL__BROWSER_UNSUPPORTED' as const;
    public static CODE_USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED' as const;

    protected constructor(code: string, step: string | null, message: string, cause: unknown = undefined) {
        super(message, { cause });
        this.name = 'PayPalPluginError';
        this._message = message;
        this._code = code;
        this._step = step;
        this.updateMessage();
    }

    public static create(code: string, step: string | null, error: unknown = undefined): PayPalPluginError {
        if (error instanceof PayPalPluginError) {
            error.step ??= step;
            return error;
        }

        const message = this.stringifyError(error);

        return new PayPalPluginError(code, step, message, error);
    }

    public static generic(step: string, error: unknown = undefined): PayPalPluginError {
        return this.create(step, this.CODE_GENERIC, error);
    }

    public static scriptLoad(script: string, error: unknown = undefined): PayPalPluginError {
        return this.create(this.CODE_GENERIC, this.STEP_SCRIPT_LOAD, `Failed to load script "${script}": ${this.stringifyError(error)}`);
    }

    public static setupFlow(code: string, error: unknown = undefined): PayPalPluginError {
        return this.create(code, this.STEP_SETUP_FLOW, error);
    }

    public static submitFlow(code: string, error: unknown = undefined): PayPalPluginError {
        return this.create(code, this.STEP_SUBMIT_FLOW, error);
    }

    public static notEligible(fundingSource: PayPalCoreJS.FundingSource): PayPalPluginError {
        return this.setupFlow(this.CODE_NOT_ELIGIBLE, `Funding for "${fundingSource}" is not eligible`);
    }

    public static browserUnsupported(fundingSource: PayPalCoreJS.FundingSource): PayPalPluginError {
        return this.setupFlow(this.CODE_BROWSER_UNSUPPORTED, `Browser does not support by "${fundingSource}"`);
    }

    public static async api(name: string, response: Response): Promise<PayPalPluginError> {
        let code = this.CODE_GENERIC;
        let error = undefined;

        try {
            // eslint-disable-next-line
            const errors = (await response.json())?.errors;

            if (Array.isArray(errors)) {
                // eslint-disable-next-line
                for (const error of errors) {
                    if (typeof error !== 'object') {
                        continue;
                    }

                    // eslint-disable-next-line
                    if (typeof error.code === 'string') {
                        // eslint-disable-next-line
                        code = error.code;
                    }
                }
            }
        } catch {
            error = await response.text();
        }

        return this.create(code, null, `API call "${name}" failed (${response.status}): ${error}`);
    }

    public static stringifyError(error: unknown): string {
        if (error instanceof Error) {
            if (error.name === 'SdkInitError') {
                error = JSON.stringify(error);
            } else {
                error = String(error);
            }
        }

        if (error && typeof error !== 'string') {
            error = JSON.stringify(error);
        }

        // eslint-disable-next-line
        return String(error ?? '');
    }

    public get code(): string {
        return this._code;
    }

    public set code(code: string) {
        this._code = code;
        this.updateMessage();
    }

    public get step(): string | null {
        return this._step;
    }

    public set step(step: string | null) {
        this._step = step;
        this.updateMessage();
    }

    public get isFatal(): boolean {
        return PayPalPluginError.FATAL_STEPS.includes(this.step ?? '');
    }

    private updateMessage(): void {
        this.message = `${this.code} occurred${this.step ? ` at ${this.step}` : ''}: ${this._message}`;
    }
}

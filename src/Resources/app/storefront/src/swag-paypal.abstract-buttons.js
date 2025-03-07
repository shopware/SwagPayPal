import SwagPayPalScriptBase from './swag-paypal.script-base';

export default class SwagPaypalAbstractButtons extends SwagPayPalScriptBase {
    static options = {
        ...super.options,

        /**
         * This option specifies the PayPal button color
         *
         * @type string
         */
        buttonColor: null,

        /**
         * This option specifies the PayPal button shape
         *
         * @type string
         */
        buttonShape: 'sharp',

        /**
         * This option specifies the PayPal button size
         *
         * @type string
         */
        buttonSize: 'small',

        /**
         * URL to create a new PayPal order
         *
         * @type string
         */
        createOrderUrl: '',

        /**
         * URL for adding flash error message
         *
         * @type string
         */
        handleErrorUrl: '',
    };

    GENERIC_ERROR = 'SWAG_PAYPAL__GENERIC_ERROR';
    NOT_ELIGIBLE = 'SWAG_PAYPAL__NOT_ELIGIBLE';
    USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED';
    BROWSER_UNSUPPORTED = 'SWAG_PAYPAL__BROWSER_UNSUPPORTED';
    SCRIPT_ERROR = 'SWAG_PAYPAL__SCRIPT_ERROR';
    SCRIPT_NOT_LOADED = 'SWAG_PAYPAL__SCRIPT_NOT_LOADED';

    /**
     * @param {String} code - The error code. Will be replaced by an extracted error code from {@link error} if available
     * @param {Boolean} [fatal=false] - A fatal error will not allow a rerender of the PayPal buttons
     * @param {*} [error=undefined] - The error. Can be any type, but will be converted to a string
     */
    handleError(code, fatal = false, error = undefined) {
        if (error instanceof Error) {
            error = String(error);
        }

        if (error && typeof error !== 'string') {
            error = JSON.stringify(error);
        }

        const errorCode = this._extractErrorCode(error);
        if (errorCode) {
            code = errorCode;
        }

        if (!this.options.handleErrorUrl) {
            console.error(`PayPal ${fatal ? 'fatal ' : ''}error occurred: ${code} - ${String(error ?? '')}`);
            return;
        }

        this._client.post(this.options.handleErrorUrl, JSON.stringify({
            code,
            error,
            fatal,
        }), () => {
            this.onErrorHandled(code, fatal, error);
        });
    }

    /**
     * Will be called after the handleErrorUrl was called. See {@link handleError}.
     *
     * @param {String} code - The error code. Will be replaced by an extracted error code from {@link error} if available
     * @param {Boolean} [fatal=false] - A fatal error will not allow a rerender of the PayPal buttons
     * @param {*} [error=undefined] - The error. Can be any type, but will be converted to a string
     */
    // eslint-disable-next-line no-unused-vars
    onErrorHandled(code, fatal, error) {
        window.scrollTo(0, 0);
        window.location.reload();
    }

    /**
     * Stop payment process with a generic __fatal__ error.
     * Will prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onFatalError(error = undefined) {
        this.handleError(this.GENERIC_ERROR, true, error);
    }

    /**
     * Stop payment process with a generic error.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onError(error = undefined) {
        this.handleError(this.GENERIC_ERROR, false, error);
    }

    /**
     * Cancel the payment process with a generic cancellation.
     * Will __NOT__ prevent rendering the button through the render function.
     *
     * @param {*} [error=undefined] - Can be any type, but will be converted to a string
     */
    onCancel(error = undefined) {
        this.handleError(this.USER_CANCELLED, false, error);
    }

    /**
     * @private
     * @returns {String|null}
     */
    _extractErrorCode(error) {
        try {
            const errors = JSON.parse(error)?.errors;

            if (!Array.isArray(errors)) {
                return null;
            }

            for (const error of errors) {
                if (typeof error !== 'object') {
                    continue;
                }

                if (typeof error.code === 'string') {
                    return error.code;
                }
            }
        } catch { /* no error handling needed */ }

        return null;
    }
}

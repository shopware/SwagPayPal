/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/helper/plugin/plugin.class';
import DomAccess from 'src/script/helper/dom-access.helper';
import HttpClient from 'src/script/service/http-client.service';

export default class SwagPayPalPlusPaymentWall extends Plugin {
    static options = {
        /**
         * The ID of the div element where the payment wall iFrame should be rendered
         *
         * @type string
         */
        placeholder: 'ppplus',

        /**
         * The approvalUrl that is returned by the `create payment` call
         *
         * @type string
         */
        approvalUrl: '',

        /**
         * The ID of the PayPal payment
         *
         * @type string
         */
        remotePaymentId: '',

        /**
         * An ISO-3166 country code.
         * Country in which the PayPal PLUS service will be deployed.
         *
         * @type string
         */
        customerCountryIso: '',

        /**
         * Depending on the mode, the library will load the PSP from different locations. live will
         * load it from paypal.com whereas sandbox will load it from sandbox.paypal.com. The
         * library will also emit warning to the console if the mode is sandbox (in live mode it will
         * do so only for required parameters).
         *
         * Available modes:
         *  - "live"
         *  - "sandbox"
         *
         * @type string
         */
        mode: 'live',

        /**
         * Determines the location of the Continue button. Don't forget to set the onContinue
         * parameter.
         *
         * Available locations:
         *  - "inside"
         *  - "outside"
         *
         *  @type string
         */
        buttonLocation: 'outside',

        /**
         * Determines if one of the following should be preselected:
         * nothing (="none"),
         * PayPal Wallet (="paypal") or
         * third party method with methodName
         *
         * @type string
         */
        preSelection: 'paypal',

        /**
         * Checkout flow to be implemented by the Merchant. If not set, the default will be set to
         * the "Continue" flow. The checkout flow selected determines whether the merchant
         * explicitly requires that the buyer reviews and confirms the payment on a review page
         * ("Continue" Flow) or if he/she can confirm payment on PayPal ("Commit" Flow).
         *
         * @type string
         */
        userAction: 'commit',

        /**
         * The language ISO (ISO_639) for the payment wall.
         *
         * @type string
         */
        customerSelectedLanguage: 'en_GB',

        /**
         * If set to "true" it will activate a message that indicates that surcharges will be applied.
         *
         * @type boolean
         */
        surcharging: false,

        /**
         * If set to "true" it will show a loading spinner until the PSP is completely rendered.
         *
         * @type boolean
         */
        showLoadingIndicator: true,

        /**
         * If set to "true" PUI is shown in sandbox mode (NOTE: this parameter is ignored in
         * production mode!)
         *
         * @type boolean
         */
        showPuiOnSandbox: true
    };

    init() {
        const confirmOrderForm = DomAccess.querySelector(document, '#confirmOrderForm');
        confirmOrderForm.addEventListener('submit', this.onConfirmCheckout.bind(this));
        this.createPaymentWall();
    }

    createPaymentWall() {
        this.loaded = false;
        this.paypal = window.PAYPAL;
        this.paymentWall = null;

        this.paymentWall = this.paypal.apps.PPP({
            placeholder: this.options.placeholder,
            approvalUrl: this.options.approvalUrl,
            mode: this.options.mode,
            country: this.options.customerCountryIso,
            buttonLocation: this.options.buttonLocation,
            language: this.options.customerSelectedLanguage,
            useraction: this.options.userAction,
            surcharging: this.options.surcharging,
            showLoadingIndicator: this.options.showLoadingIndicator,
            showPuiOnSandbox: this.options.showPuiOnSandbox,
            onLoad: this.onLoad.bind(this),
            enableContinue: this.onEnableContinue.bind(this)
        });
    }

    onLoad() {
        this.loaded = true;
        const selectedPaymentId = SwagPayPalPlusPaymentWall.getSelectedPaymentMethodId();

        if (selectedPaymentId !== this.options.paymentMethodId) {
            this.clearPaymentSelection();
        }
    }

    /**
     * Returns the currently selected payment id.
     *
     * @returns {String}
     */
    static getSelectedPaymentMethodId() {
        const selectedPaymentMethodRadio = DomAccess.querySelector(
            document,
            '*[checked="checked"][name="paymentMethodId"]'
        );

        return DomAccess.getAttribute(selectedPaymentMethodRadio, 'value');
    }

    /**
     * This function deselect any payment method inside the iFrame
     */
    clearPaymentSelection() {
        if (this.loaded) {
            this.paymentWall.deselectPaymentMethod();
        }
    }

    /**
     * This function will be triggered if the "enableContinue" event was fired inside the iFrame.
     * In addition to that, this event can be used to determine if the user has clicked on one of the payment
     * methods inside the iFrame. If so, it has to be checked, if PayPal is selected as payment method or not
     */
    onEnableContinue() {
        if (this.loaded) {
            const paypalRadio = DomAccess.querySelector(
                document,
                `*[name=paymentMethodId][value="${this.options.paymentMethodId}"]`
            );

            const selectedPaymentId = SwagPayPalPlusPaymentWall.getSelectedPaymentMethodId();

            if (selectedPaymentId !== this.options.paymentMethodId && !DomAccess.hasAttribute(paypalRadio, 'checked')) {
                paypalRadio.setAttribute('checked', 'checked');

                const paymentForm = DomAccess.querySelector(document, '#confirmPaymentForm');
                paymentForm.dispatchEvent(new Event('change'));
            }
        }
    }

    /**
     * Will be triggered when the confirm form was submitted.
     * In this case, the order will be patched and the PayPal
     * checkout process will be triggered afterwards
     *
     * @param {Event} event
     */
    onConfirmCheckout(event) {
        const selectedPaymentId = SwagPayPalPlusPaymentWall.getSelectedPaymentMethodId();
        if (selectedPaymentId !== this.options.paymentMethodId) {
            return;
        }

        event.preventDefault();
        if (!event.target.checkValidity()) {
            return;
        }

        this._client = new HttpClient(window.accessKey, window.contextToken);
        this._client.post('/sales-channel-api/v1/checkout/order', JSON.stringify({}), this.afterCreateOrder.bind(this));
    }

    /**
     * @param {String} response
     */
    afterCreateOrder(response) {
        const order = JSON.parse(response);
        const orderId = order.data.id;
        const params = {
            isPayPalPlusEnabled: true,
            remotePaymentId: this.options.remotePaymentId
        };

        this._client.post(
            `/sales-channel-api/v1/checkout/order/${orderId}/pay`,
            JSON.stringify(params),
            this.afterPayOrder.bind(this)
        );
    }

    afterPayOrder(response) {
        const data = JSON.parse(response);

        if (data.paymentUrl === 'plusPatched') {
            this.paypal.apps.PPP.doCheckout();
        }
    }
}

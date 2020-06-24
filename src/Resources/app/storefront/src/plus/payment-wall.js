/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import StoreApiClient from 'src/service/store-api-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

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
        paypalPaymentId: '',

        /**
         * The token of the PayPal payment
         *
         * @type string
         */
        paypalToken: '',

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
        showPuiOnSandbox: true,

        /**
         * URL for creating and paying the Shopware order
         *
         * @type string
         */
        checkoutOrderUrl: '',

        /**
         * URL for setting the payment method to the order
         *
         * @type string
         */
        setPaymentRouteUrl: '',

        /**
         * Request parameter name which identifies a PLUS checkout
         *
         * @type string
         */
        isEnabledParameterName: 'isPayPalPlusCheckout',

        /**
         * Is set, if the plugin is used on the order edit page
         *
         * @type string|null
         */
        orderId: null
    };

    init() {
        const confirmOrderForm = DomAccess.querySelector(document, '#confirmOrderForm');
        confirmOrderForm.addEventListener('submit', this.onConfirmCheckout.bind(this));
        this.createPaymentWall();
    }

    createPaymentWall() {
        this.paypal = window.PAYPAL;

        this.paypal.apps.PPP({
            placeholder: this.options.placeholder,
            approvalUrl: this.options.approvalUrl,
            mode: this.options.mode,
            country: this.options.customerCountryIso,
            buttonLocation: this.options.buttonLocation,
            language: this.options.customerSelectedLanguage,
            useraction: this.options.userAction,
            surcharging: this.options.surcharging,
            showLoadingIndicator: this.options.showLoadingIndicator,
            showPuiOnSandbox: this.options.showPuiOnSandbox
        });
    }

    /**
     * Will be triggered when the confirm form was submitted.
     * In this case, the order will be patched and the PayPal
     * checkout process will be triggered afterwards
     *
     * @param {Event} event
     */
    onConfirmCheckout(event) {
        event.preventDefault();
        if (!event.target.checkValidity()) {
            return;
        }

        this._client = new StoreApiClient();
        const data = {
            _csrf_token: DomAccess.getDataAttribute(this.el, 'swag-pay-pal-plus-payment-wall-checkout-order-token')
        };

        ElementLoadingIndicatorUtil.create(document.body);

        const orderId = this.options.orderId;
        if (orderId !== null) {
            data.orderId = orderId;
            data.paymentMethodId = this.options.paymentMethodId;

            this._client.post(this.options.setPaymentRouteUrl, JSON.stringify(data), this.afterSetPayment.bind(this));

            return;
        }

        this._client.post(this.options.checkoutOrderUrl, JSON.stringify(data), this.afterCreateOrder.bind(this));
    }

    /**
     * @param {String} response
     */
    afterCreateOrder(response) {
        const order = JSON.parse(response);
        const orderId = order.data.id;
        const params = {
            paypalPaymentId: this.options.paypalPaymentId,
            paypalToken: this.options.paypalToken
        };
        params[this.options.isEnabledParameterName] = true;

        this._client.post(
            `${this.options.checkoutOrderUrl}/${orderId}/pay`,
            JSON.stringify(params),
            this.afterPayOrder.bind(this)
        );
    }

    afterSetPayment(response) {
        const responseObject = JSON.parse(response);
        if (responseObject.success === true) {
            this.afterCreateOrder(JSON.stringify({data: {id: this.options.orderId}}))
        }
    }

    afterPayOrder(response) {
        const data = JSON.parse(response);

        if (data.paymentUrl === 'plusPatched') {
            this.paypal.apps.PPP.doCheckout();
        }
    }
}

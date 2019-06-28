/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import DomAccess from 'src/script/helper/dom-access.helper';

/**
 * This Plugin selects the PayPal payment method if the user is being redirected by the express implementation.
 */
export default class PayPalSelector extends Plugin {
    static options = {
        /**
         * This option is used to select the PayPal radio button
         */
        paypalPaymentMethodId: ''
    };

    init() {
        this.selectPaymentMethodPayPal();
    }

    selectPaymentMethodPayPal() {
        const paypalRadioButton = DomAccess.querySelector(
            document.body,
            `input[value="${this.options.paypalPaymentMethodId}"]`
        );

        if (paypalRadioButton) {
            paypalRadioButton.checked = true;
        }
    }
}

import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import SwagPaypalAbstractStandalone from './swag-paypal.abstract-standalone';

export default class SwagPayPalSmartPaymentButtons extends SwagPaypalAbstractStandalone {
    static options = {
        ...super.options,
        buttonColor: 'gold',
    };

    /**
     * @deprecated tag:v10.0.0 - will be removed without replacement
     */
    init() {
        this._client = new HttpClient();

        this.createButton();
    }

    /**
     * @deprecated tag:v10.0.0 - will part of `init` and be removed without replacement
     */
    createButton() {
        this.createScript((paypal) => {
            this.renderButton(paypal);
        });
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed and replaced by {@see render}
     */
    renderButton(paypal) {
        this.confirmOrderForm = DomAccess.querySelector(document, this.options.confirmOrderFormSelector);

        DomAccess.querySelector(this.confirmOrderForm, this.options.confirmOrderButtonSelector).classList.add('d-none');

        return paypal.Buttons(this.getButtonConfig()).render(this.el);
    }

    getFundingSource() {
        return undefined;
    }

    /**
     * @deprecated tag:v10.0.0 - `fundingSource` parameter will be mandatory
     */
    getButtonConfig(fundingSource = this.getFundingSource()) {
        return super.getButtonConfig(fundingSource);
    }

    /**
     * @deprecated tag:v10.0.0 - `product` parameter will be mandatory
     *
     * @return {Promise}
     */
    createOrder(product = 'spb') {
        return super.createOrder(product);
    }
}

import SwagPaypalAbstractButtons from "../swag-paypal.abstract-buttons";
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import HttpClient from "src/service/http-client.service";

export default class SwagPaypalFastlane extends SwagPaypalAbstractButtons {
    static options = {
        ...super.options,

        /**
         * @type string
         */
        sdkClientToken: '',

        /**
         * @type string
         */
        prepareCheckoutUrl: '',

        /**
         * @type string
         */
        checkoutConfirmUrl: '',
    };

    fastlane = null;

    init() {
        this._client = new HttpClient();

        if (!this.options.sdkClientToken) {
            return;
        }

        this.createScript((paypal) => {
            this.render(paypal);
        });
    }

    async render(paypal) {

        // instantiates the Fastlane module
        this.fastlane = await paypal.Fastlane({ });
        this.fastlane.setLocale("en_us");

        this.el.addEventListener('change', this.lookupUser.bind(this));
    }

    async lookupUser(event) {
        const email = this.el.value;
        const searchResult = await this.fastlane.identity.lookupCustomerByEmail(this.el.value);
        if (!searchResult.customerContextId) {
            console.log(`Customer ${email} not found, continue with normal registration flow.`);

            return;
        }
        console.log(`Customer ${email} found, customer context id: ${searchResult.customerContextId}.`)

        const authenticationResult = await this.fastlane.identity.triggerAuthenticationFlow(searchResult.customerContextId);
        if (authenticationResult.authenticationState !== "succeeded") {
            console.log("Authentication failed.");

            return;
        }


        console.log(authenticationResult.profileData);
        PageLoadingIndicatorUtil.create(true);
        this._client.post(
            this.options.prepareCheckoutUrl,
            JSON.stringify({
                email: this.el.value,
                profileData: authenticationResult.profileData,
            }),
            (response, request) => {
                if (request.status < 400) {
                    window.location.href = this.options.checkoutConfirmUrl;
                }

                return this.onError();
            }
        );
    }
}

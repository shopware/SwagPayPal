import SwagPaypalAbstractButtons from "../swag-paypal.abstract-buttons";
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import HttpClient from "src/service/http-client.service";
import ElementLoadingIndicatorUtil from "src/utility/loading-indicator/element-loading-indicator.util";

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

        document.querySelector('.register-card').classList.add('d-none');

        this.createScript((paypal) => {
            this.render(paypal);
        });
    }

    async render(paypal) {

        // instantiates the Fastlane module
        this.fastlane = await paypal.Fastlane({ });

        this.fastlane.setLocale("en_us");
        const lookupButton = this.el.addEventListener('submit', this.lookupUser.bind(this));
        const component = await this.fastlane.FastlaneWatermarkComponent();

        component.render('.swag-paypal-fastlane-watermark');
    }

    async lookupUser(event) {
        event.preventDefault();
        ElementLoadingIndicatorUtil.create(this.el.querySelector('#fastlaneLookup'));
        const email = this.el.querySelector('#fastlaneEmail').value;
        const searchResult = await this.fastlane.identity.lookupCustomerByEmail(email);
        if (!searchResult.customerContextId) {
            console.log(`Customer ${email} not found, continue with normal registration flow.`);
            this.continueWithRegistration(email);

            return;
        }
        console.log(`Customer ${email} found, customer context id: ${searchResult.customerContextId}.`)

        const authenticationResult = await this.fastlane.identity.triggerAuthenticationFlow(searchResult.customerContextId);
        if (authenticationResult.authenticationState !== "succeeded") {
            console.log("Authentication failed.");
            this.continueWithRegistration(email);

            return;
        }


        console.log(authenticationResult.profileData);
        PageLoadingIndicatorUtil.create(true);
        this._client.post(
            this.options.prepareCheckoutUrl,
            JSON.stringify({
                email,
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

    continueWithRegistration(email) {
        const registrationForm = document.querySelector('.register-card');
        registrationForm.classList.remove('d-none');
        this.el.classList.add('d-none');

        ElementLoadingIndicatorUtil.remove(this.el.querySelector('#fastlaneLookup'));

        registrationForm.querySelector('#personalMail').value = email;

    }
}

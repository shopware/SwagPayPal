import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

const SwagPayPalSpbMarksInstances = [];
let isInjectionTriggered = false;

export default class SwagPayPalMarks extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: '',

        /**
         * The selector for the indicator whether the PayPal javascript is already loaded or not
         *
         * @type string
         */
        paypalScriptLoadedClass: 'paypal-marks-js-loaded'
    };

    init() {
        this.paypal = null;
        SwagPayPalSpbMarksInstances.push(this);
        this.createMarks();
    }

    createMarks() {
        const paypalScriptLoaded = document.head.classList.contains(this.options.paypalScriptLoadedClass);

        if (paypalScriptLoaded) {
            this.paypal = window.paypal;
            this.paypal.Marks().render(this.el);
            return;
        }

        if (isInjectionTriggered) {
            return;
        }

        isInjectionTriggered = true;
        this.createScript(() => {
            this.paypal = window.paypal;
            document.head.classList.add(this.options.paypalScriptLoadedClass);

            SwagPayPalSpbMarksInstances.forEach((instance) => {
                this.paypal.Marks().render(instance.el);
            });
        });
    }

    getScriptUrlOptions() {
        return '&components=marks';
    }
}

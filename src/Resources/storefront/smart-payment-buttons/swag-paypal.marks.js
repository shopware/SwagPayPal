import SwagPaypalAbstractButtons from '../swag-paypal.abstract-buttons';

const SwagPayPalSpbMarksInstances = [];

export default class SwagPayPalMarks extends SwagPaypalAbstractButtons {
    static options = {
        /**
         * This option holds the client id specified in the settings
         *
         * @type string
         */
        clientId: ''
    };

    init() {
        this.paypal = null;
        SwagPayPalSpbMarksInstances.push(this);
        this.createMarks();
    }

    createMarks() {
        this.createScript(() => {
            this.paypal = window.paypal;

            SwagPayPalSpbMarksInstances.forEach((instance) => {
                this.paypal.Marks().render(instance.el);
            });
        });
    }
}

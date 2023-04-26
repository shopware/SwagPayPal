import template from './swag-paypal-pui-details.html.twig';
import './swag-paypal-pui-details.scss';

const { Component } = Shopware;
const domUtils = Shopware.Utils.dom;

Component.register('swag-paypal-pui-details', {
    template,

    props: {
        details: {
            type: Object,
            required: true,
        },

        purchaseUnitAmount: {
            type: Object,
            required: true,
        },
    },

    computed: {
        copyText() {
            return `${this.$tc('swag-paypal-payment.puiDetails.bank')} ${this.bankName}
${this.$tc('swag-paypal-payment.puiDetails.iban')} ${this.iban}
${this.$tc('swag-paypal-payment.puiDetails.bic')} ${this.bic}
${this.$tc('swag-paypal-payment.puiDetails.accountHolder')} ${this.accountHolderName}
${this.$tc('swag-paypal-payment.puiDetails.amount')} ${this.amount}
${this.$tc('swag-paypal-payment.puiDetails.reference')} ${this.reference}`;
        },

        bankName() {
            return this.details.deposit_bank_details.bank_name;
        },

        iban() {
            return this.details.deposit_bank_details.iban;
        },

        bic() {
            return this.details.deposit_bank_details.bic;
        },

        accountHolderName() {
            return this.details.deposit_bank_details.account_holder_name;
        },

        reference() {
            return this.details.payment_reference;
        },

        amount() {
            return `${this.purchaseUnitAmount.value} ${this.purchaseUnitAmount.currency_code}`;
        },
    },

    methods: {
        async onCopy() {
            if (!navigator?.clipboard) {
                // non-https polyfill
                domUtils.copyToClipboard(this.copyText);

                return;
            }

            await navigator.clipboard.writeText(this.copyText);
        },
    },
});

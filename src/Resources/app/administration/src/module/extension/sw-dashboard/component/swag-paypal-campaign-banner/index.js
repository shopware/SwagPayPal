import template from './swag-paypal-campaign-banner.html.twig';
import './swag-paypal-campaign-banner.scss';

const { Component } = Shopware;

/**
 * @private may be removed oder changed at any time
 */
Component.register('swag-paypal-campaign-banner', {
    template,

    i18n: {
        messages: {
            'de-DE': {
                '2022-08': {
                    title: '„Kauf auf Rechnung“ wird als Teil von PayPal PLUS eingestellt',
                    text: 'Wechsle zur neuen Komplettlösung PayPal Checkout, ' +
                        'um Deinen Kunden auch weiterhin den Rechnungskauf anzubieten. ',
                    labelText: 'Handlungsbedarf bis 30.09.22',
                },
                linkTitle: 'Zu den PayPal-Einstellungen',
            },
            'en-GB': {
                '2022-08': {
                    title: '“Purchase upon invoice” will be discontinued as part of PayPal PLUS',
                    text: 'Switch to the new all-in-one PayPal Checkout solution ' +
                        'to continue offering pay upon invoice to your customers. ' +
                        'Switch to PayPal Checkout now!',
                    labelText: 'Action required by 30/09/22',
                },
                linkTitle: 'Go to PayPal settings',
            },
        },
    },

    data() {
        return {
            closed: true,
        };
    },

    computed: {
        linkTitle() {
            return this.$tc('linkTitle');
        },

        timePrefix() {
            return '2022-08';
        },

        labelText() {
            return this.$tc(`${this.timePrefix}.labelText`);
        },

        title() {
            return this.$tc(`${this.timePrefix}.title`);
        },

        text() {
            return this.$tc(`${this.timePrefix}.text`);
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },

        currentLocale() {
            return Shopware.State.get('session').currentLocale;
        },

        image() {
            const suffix = this.currentLocale === 'de-DE' ? 'de' : 'en';

            return this.assetFilter(`swagpaypal/static/img/campaign/${this.timePrefix}_${suffix}.png`);
        },

        cardClasses() {
            return {
                'sw-campaign-banner': true,
                'swag-paypal-campaign-banner': true,
                'swag-paypal-campaign-banner__closed': this.closed,
            };
        },

        localStorageKey() {
            return `swag-paypal-campaign-banner.${this.timePrefix}.closed`;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.closed = window.localStorage.getItem(this.localStorageKey) === 'true';
        },

        close() {
            this.closed = true;

            window.localStorage.setItem(this.localStorageKey, 'true');
        },
    },
});

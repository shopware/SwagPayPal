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
                '2022-07': {
                    title: 'PayPal passt Standardgebühren an',
                    text: 'Sichere dir jetzt deine alten Gebühren für ein weiteres Jahr: ' +
                        'PayPal Checkout aktivieren und 3 Transaktionen bis 22.07.2022 abschließen.',
                    labelText: 'Nur bis 22.07.22',
                },
                '2022-08': {
                    title: '„Kauf auf Rechnung“ wird als Teil von PayPal PLUS eingestellt',
                    text: 'Wechsel zur neuen Komplettlösung PayPal Checkout, ' +
                        'um Deinen Kunden auch weiterhin den Rechnungskauf anbieten zu können. ' +
                        'Jetzt zu PayPal Checkout wechseln!',
                    labelText: 'Handlungsbedarf bis 30/09/22',
                },
                linkTitle: 'Ins PayPal-Plugin',
            },
            'en-GB': {
                '2022-07': {
                    title: 'PayPal adjusts standard fees',
                    text: 'Maintain your old pricing for another year now! ' +
                        'Activate PayPal Checkout and complete three transactions by 22/07/2022',
                    labelText: 'Limited until 22/07/22',
                },
                '2022-08': {
                    title: '“Purchase upon invoice” will be discontinued as part of PayPal PLUS',
                    text: 'Switch to the new all-in-one PayPal Checkout solution ' +
                        'to continue offering purchase upon invoice to your customers. ' +
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
            return new Date() >= new Date('2022-07-23') ? '2022-08' : '2022-07';
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

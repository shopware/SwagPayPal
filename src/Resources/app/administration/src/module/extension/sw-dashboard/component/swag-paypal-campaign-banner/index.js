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
                '2022-10': {
                    title: 'Spare bares Geld!',
                    text: 'Am 01.08.2022 hat PayPal die Standardgebühren angepasst. ' +
                        'Migriere bis 31.12.2022 zu PayPal Checkout* und behalte deine alten Gebühren bis 31.07.2023.<br>' +
                        // eslint-disable-next-line max-len
                        '* Erfahre mehr in unserem <a href="https://www.shopware.com/de/news/paypal-aktualisiert-gebuehren/" target="_blank">Blog Beitrag</a>',
                    labelText: '',
                },
                linkTitle: 'Zu den PayPal-Einstellungen',
            },
            'en-GB': {
                '2022-10': {
                    title: 'Save money now!',
                    text: 'On 1 August 2022, PayPal adjusted the standard fees. ' +
                        // eslint-disable-next-line max-len
                        'Migrate to PayPal Checkout by 31 December 2022* and maintain your old prices until 31 July 2023!<br>' +
                        // eslint-disable-next-line max-len
                        '* Learn more about in our <a href="https://www.shopware.com/en/news/paypal-updated-fees/" target="_blank">blog</a>',
                    labelText: '',
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
            return '2022-10';
        },

        labelText() {
            return this.$tc(`${this.timePrefix}.labelText`);
        },

        showLabel() {
            return this.labelText !== `${this.timePrefix}.labelText`;
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

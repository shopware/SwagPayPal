import template from './swag-paypal-pos-getting-started.html.twig';
import './swag-paypal-pos-getting-started.scss';
import paypalPosApp from 'SwagPayPal/static/img/paypal-pos-app.png';
import paypalPosReader from 'SwagPayPal/static/img/paypal-pos-reader.png';

const { Component } = Shopware;

Component.register('swag-paypal-pos-getting-started', {
    template,

    data() {
        return {
            paypalPosApp,
            paypalPosReader,
        };
    },
});

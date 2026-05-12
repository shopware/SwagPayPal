import template from './swag-paypal-pos-continue-setup.html.twig';
import './swag-paypal-pos-continue-setup.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-continue-setup', {
    template,

    methods: {
        onContinueSetup() {
            this.$router.push(
                {
                    name: 'swag.paypal.pos.wizard.customization',
                    params: { id: this.$route.params.id },
                },
            );
        },
    },
});

import template from './swag-paypal-izettle-continue-setup.html.twig';
import './swag-paypal-izettle-continue-setup.scss';

const { Component } = Shopware;

Component.register('swag-paypal-izettle-continue-setup', {
    template,

    methods: {
        onContinueSetup() {
            this.$router.push(
                {
                    name: 'swag.paypal.izettle.wizard.customization',
                    params: { id: this.$route.params.id }
                }
            );
        }
    }
});

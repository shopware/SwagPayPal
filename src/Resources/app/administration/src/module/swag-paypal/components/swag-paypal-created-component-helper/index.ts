import template from './swag-paypal-created-component-helper.html.twig';

const { Component } = Shopware;

/* This component exists only to implement the createdComponent live-cycle
 * hook in without using it of the actual component. the reason is that there
 * are problems in the cloud with other plugins (e.g. Mollie) that already
 * overwrite created-Component. Since the order of plugin overrides is not deterministic,
 * race conditions can occur here.
*/

Component.register('swag-paypal-created-component-helper', {
    template,

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$emit('on-created-component');
        },
    },
});

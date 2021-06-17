import template from './swag-paypal-textarea-field.html.twig';

const { Component } = Shopware;

/**
 * @protected
 * @description textarea input field. But this one allows attribute downpassing to the input field instead of the block.
 * @status ready
 * @example-type static
 * @component-example
 * <swag-paypal-textarea-field label="Name" placeholder="placeholder goes here..."></swag-paypal-textarea-field>
 */
Component.extend('swag-paypal-textarea-field', 'sw-textarea-field', {
    template,
});

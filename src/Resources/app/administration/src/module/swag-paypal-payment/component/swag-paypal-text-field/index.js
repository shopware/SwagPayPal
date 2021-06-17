import template from './swag-paypal-text-field.html.twig';

const { Component } = Shopware;

/**
 * @protected
 * @description Simple text field. But this one allows attribute downpassing to the input field instead of the block.
 * @status ready
 * @example-type dynamic
 * @component-example
 * <swag-paypal-text-field label="Name" placeholder="placeholder goes here..."></swag-paypal-text-field>
 */
Component.extend('swag-paypal-text-field', 'sw-text-field', {
    template,
});

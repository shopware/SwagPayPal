import template from './sw-plugin-box.html.twig';

export default Shopware.Component.wrapComponentConfig({
    template,

    computed: {
        isPayPalMethod(): boolean {
            // @ts-expect-error - plugin is from extended component
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return this.plugin?.name === 'SwagPayPal';
        },
    },
});

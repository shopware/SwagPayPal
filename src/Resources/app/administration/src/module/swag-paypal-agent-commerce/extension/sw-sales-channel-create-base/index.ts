import template from './sw-sales-channel-create-base.html.twig';

const exportHeader = () => import('SwagPayPal/module/swag-paypal-agent-commerce/static/product-export/header.txt?raw');
const exportBody = () => import('SwagPayPal/module/swag-paypal-agent-commerce/static/product-export/body.txt?raw');

export default Shopware.Component.wrapComponentConfig<{ productExport: TEntity<'product_export'> }>({
    template,

    methods: {
        async createdComponent() {
            this.$super('createdComponent');

            this.productExport.name = 'Paypal Agent Commerce Export';
            this.productExport.translationKey = 'swag-paypal.sw-sales-channel.productComparison.templates.template-label.commerce-agent';
            this.productExport.footerTemplate = '';
            this.productExport.fileName = 'paypal-agent-commerce-export.csv';
            this.productExport.encoding = 'UTF-8';
            this.productExport.fileFormat = 'csv';
            this.productExport.generateByCronjob = false;
            this.productExport.interval = 86400;

            await Promise.all([exportHeader(), exportBody()]).then(([header, body]: [header: string, body: string]) => {
                this.productExport.headerTemplate = header.replace(/[\r\n]+/g, '').trim();
                this.productExport.bodyTemplate = body.replace(/[\r\n]+/g, '').trim();
            });
        },
    },

    computed: {
        isProductComparison() {
            return true;
        },
    },
});

import exportHeader from 'SwagPayPal/module/swag-paypal-agent-commerce/static/product-export/header.csv.twig?raw';
import exportBody from 'SwagPayPal/module/swag-paypal-agent-commerce/static/product-export/body.csv.twig?raw';

export default Shopware.Component.wrapComponentConfig<{ salesChannel: TEntity<'sales_channel'>; productExport: TEntity<'product_export'> }>({
    methods: {
        /* eslint-disable @typescript-eslint/no-unsafe-assignment, @typescript-eslint/no-unsafe-member-access, @typescript-eslint/no-unsafe-call*/
        async createdComponent() {
            // create dummy sales channel to avoid race condition in base components
            // @ts-expect-error - salesChannelRepository is defined in the parent component
            this.salesChannel = this.salesChannelRepository.create();
            this.salesChannel.typeId = this.$route.params.typeId as string;

            await this.$super('createdComponent');

            this.productExport.name = this.$tc('swag-paypal-agent-commerce.product-export.name');
            this.productExport.translationKey = 'swag-paypal-agent-commerce.sw-sales-channel.productComparison.templates.template-label.commerce-agent';
            this.productExport.footerTemplate = '';
            this.productExport.fileName = `paypal-agent-commerce-export-${this.salesChannel.id}.csv`;
            this.productExport.encoding = 'UTF-8';
            this.productExport.fileFormat = 'csv';
            this.productExport.generateByCronjob = false;
            this.productExport.interval = 86400;

            this.productExport.headerTemplate = exportHeader.replace(/[\r\n]+/g, '');
            this.productExport.bodyTemplate = exportBody.replace(/[\r\n]+/g, '');
        },
    },
});

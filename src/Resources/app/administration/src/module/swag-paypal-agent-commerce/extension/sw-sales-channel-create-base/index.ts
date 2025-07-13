import template from './sw-sales-channel-create-base.html.twig';

import exportHeader from '../../static/product-export/header.csv.txt?raw';
import exportBody from '../../static/product-export/body.csv.txt?raw';


export default Shopware.Component.wrapComponentConfig<{ productExport: TEntity<'product_export'> }>({
    template,

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.productExport.name = 'Paypal Agent Commerce Export';
            this.productExport.translationKey = 'swag-paypal.sw-sales-channel.productComparison.templates.template-label.commerce-agent';
            this.productExport.headerTemplate = exportHeader.replace(/[\r\n]+/g, '').trim();
            this.productExport.bodyTemplate = exportBody.replace(/[\r\n]+/g, '').trim();
            this.productExport.footerTemplate = '';
            this.productExport.fileName = 'paypal-agent-commerce-export.csv';
            this.productExport.encoding = 'UTF-8';
            this.productExport.fileFormat = 'csv';
            this.productExport.generateByCronjob = false;
            this.productExport.interval = 86400;
        },
    },

    computed: {
        isProductComparison() {
            return true;
        },
    },
});

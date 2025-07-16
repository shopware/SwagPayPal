/**
 * @sw-package discovery
 */

import header from './header.csv.twig';
import body from './body.csv.twig';

// TODO: Don't register. Should be static for new paypal type
Shopware.Service('exportTemplateService').registerProductExportTemplate({
    name: 'paypa-agentic-commerce',
    translationKey: 'agentic-commerce',
    headerTemplate: header,
    bodyTemplate: body,
    fileName: 'paypa-agentic-commerce.csv',
    encoding: 'UTF-8',
    fileFormat: 'csv',
    generateByCronjob: false,
    interval: 86400,
});

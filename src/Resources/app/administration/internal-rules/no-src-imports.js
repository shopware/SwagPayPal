"use strict";

module.exports = {
    create: function(context) {
        return {
            ImportDeclaration(node) {
                // allow src imports in tests
                if ((new RegExp('\\.spec.[t|j]s')).test(context.getFilename())) {
                    return;
                }

                if (node.source.value.startsWith('src/')) {
                    context.report({
                        loc: node.source.loc.start,
                        message: 'Do not use imports from the Shopware Core'
                    })
                }
            }
        }
    }
}

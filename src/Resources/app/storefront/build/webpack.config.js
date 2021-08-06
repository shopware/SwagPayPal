const { join, resolve } = require('path');

module.exports = () => {
    return {
        resolve: {
            alias: {
                '@paypal': resolve(
                    join(__dirname, '..', 'node_modules', '@paypal'),
                ),
            },
        },
    };
};

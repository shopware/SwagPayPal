module.exports = (params) => {
    return {
        resolve: {
            modules: [
                `${params.basePath}/Resources/app/storefront/node_modules`,
            ],
        },
    };
};

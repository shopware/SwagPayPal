const { resolve } = require('path');
const process = require('process');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const isDevMode = (process.env.ENV === 'development');
const isHotMode = (process.env.MODE === 'hot');
const babelrc = require('./.babelrc');

let config = {
    entry: './main.js',
    mode: process.env.ENV,
    performance: {
        hints: (isDevMode ? 'warning' : false)
    },
    context: resolve(__dirname, 'src/Resources/views/storefront'),
    output: {
        path: resolve(__dirname, 'src/Resources/public/static/storefront'),
        filename: 'swag-paypal-storefront.bundle.js'
    },
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                        options: babelrc
                    }
                ]
            },
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader // extract css files from the js code
                    },
                    {
                        loader: 'css-loader'
                    },
                    {
                        loader: 'sass-loader'
                    }
                ]
            }]
    },
    resolve: {
        alias: {
            src: resolve(__dirname, '../../../platform/src/Storefront/Resources/src')
        }
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/swag-paypal-storefront.bundle.css',
            chunkFilename: 'css/swag-paypal-storefront.bundle.css'
        })
    ]
};

if (isDevMode && isHotMode) {
    config = {
        ...config,
        ...{
            devServer: {
                contentBase: resolve(__dirname, 'src/Resources/public/static/storefront'),
                compress: true,
                port: 9000,
                host: '0.0.0.0',
                hot: true,
                quiet: true,
                disableHostCheck: true,
                open: false,
                public: 'http://localhost:9000',
                headers: {
                    'Access-Control-Allow-Origin': '*'
                }
            }
        }
    };
}

module.exports = config;

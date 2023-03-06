const { resolve } = require('path');

module.exports = {
    rules: {
        'no-src-imports': require(resolve(__dirname, 'no-src-imports.js'))
    }
};

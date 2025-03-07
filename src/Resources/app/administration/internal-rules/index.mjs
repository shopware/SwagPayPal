import noSrcImportsRule from './no-src-imports.rule.mjs';

export default {
    meta: {
        name: 'eslint-plugin-internal-rules',
        version: '1.0.0',
    },
    rules: {
        'no-src-imports': noSrcImportsRule,
    },
};

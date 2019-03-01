#!/usr/bin/env bash
echo "Fix php files"
./../../../vendor/shopware/platform/bin/php-cs-fixer.phar fix --config=../../../vendor/shopware/platform/.php_cs.dist -vv .

echo "Fix javascript files"
../../../vendor/shopware/platform/src/Administration/Resources/administration/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Administration/Resources/administration/.eslintrc.js --ext .js,.vue --fix .

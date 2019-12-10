#!/usr/bin/env bash
echo "Fix php files"
php ../../../dev-ops/analyze/vendor/bin/php-cs-fixer fix --config=../../../vendor/shopware/platform/.php_cs.dist -vv .
php ../../../dev-ops/analyze/vendor/bin/php-cs-fixer fix --config=.php_cs.dist -vv .

echo "Fix javascript files"
../../../vendor/shopware/platform/src/Administration/Resources/administration/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Administration/Resources/administration/.eslintrc.js --ext .js,.vue --fix .

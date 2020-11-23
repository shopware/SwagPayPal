#!/usr/bin/env bash
echo "Fix php files"
php ../../../dev-ops/analyze/vendor/bin/ecs check --fix --config=../../../vendor/shopware/platform/easy-coding-standard.php src tests
php ../../../dev-ops/analyze/vendor/bin/ecs check --fix --config=easy-coding-standard.yml

echo "Fix javascript files"
make administration-fix
make storefront-fix

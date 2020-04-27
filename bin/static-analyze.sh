#!/usr/bin/env bash

composer dump-autoload
php "`dirname \"$0\"`"/phpstan-config-generator.php
php ../../../dev-ops/analyze/vendor/bin/phpstan analyze --configuration phpstan.neon src tests

# Return if phpstan returns with error
if [ $? -eq 1 ]
then
  exit 1
fi

php ../../../dev-ops/analyze/vendor/bin/psalm --config=psalm.xml --show-info=false --threads=$(nproc)

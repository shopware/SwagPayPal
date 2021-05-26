#!/usr/bin/env bash

composer dump-autoload
php "`dirname \"$0\"`"/phpstan-config-generator.php
php ../../../dev-ops/analyze/vendor/bin/phpstan analyze --configuration phpstan.neon src tests

# Return if phpstan returns with error
if [ $? -eq 1 ]
then
  exit 1
fi

# If composer.lock is not a file, create it with composer, because if it is not present, the caching of Psalm does not work. See https://github.com/vimeo/psalm/issues/4941
if [ ! -f "composer.lock" ]; then echo "Generating composer.lock ..."; composer update --no-install -q; fi
php ../../../dev-ops/analyze/vendor/bin/psalm --config=psalm.xml --threads=$(nproc) --diff --show-info=false

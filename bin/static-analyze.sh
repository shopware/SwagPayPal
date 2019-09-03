#!/usr/bin/env bash

composer dump-autoload
php ../../../dev-ops/analyze/vendor/bin/phpstan analyze --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php src tests

#!/usr/bin/env bash

./../../../vendor/shopware/platform/bin/phpstan.phar analyze --level 5 --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php .

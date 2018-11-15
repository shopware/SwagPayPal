#!/usr/bin/env bash

./../../../vendor/shopware/platform/bin/phpstan.phar analyze --level 7 --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php .

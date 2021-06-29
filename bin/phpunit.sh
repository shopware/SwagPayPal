#!/usr/bin/env bash

composer dump-autoload --dev
touch vendor/composer/InstalledVersions.php
./../../../vendor/bin/phpunit "$@"

#!/usr/bin/env bash

composer dump-autoload
touch vendor/composer/InstalledVersions.php
./../../../vendor/bin/phpunit "$@"

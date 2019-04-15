#!/usr/bin/env bash

composer dump-autoload
./../../../vendor/bin/phpunit --coverage-html coverage

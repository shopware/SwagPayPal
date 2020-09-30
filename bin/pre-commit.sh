#!/usr/bin/env bash

PLATFORM_ROOT="$(git rev-parse --show-toplevel)"
PROJECT_ROOT="${PROJECT_ROOT:-"$(cd "$PLATFORM_ROOT"/.. && git rev-parse --show-toplevel)"}"
AUTOLOAD_FILE="$PROJECT_ROOT/vendor/autoload.php"

function onExit {
    if [[ $? != 0 ]]
    then
        echo "Fix the error before commit."
    fi
}
trap onExit EXIT

PHP_FILES="$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '\.(php)$')"
JS_ADMIN_FILES="$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '^src/Resources/app/administration/.*\.(js)$')"
JS_STOREFRONT_FILES="$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '^src/Resources/app/storefront/.*\.(js)$')"

# exit on non-zero return code
set -e

if [[ -z "$PHP_FILES" && -z "$JS_ADMIN_FILES" && -z "$JS_STOREFRONT_FILES" ]]
then
    exit 0
fi

if [[ -n "$PHP_FILES" ]]
then
    for FILE in ${PHP_FILES}
    do
        php -l -d display_errors=0 "$FILE" 1> /dev/null
    done

    ./bin/static-analyze.sh
fi

UNSTAGED_FILES="$(git diff --name-only -- ${PHP_FILES} ${JS_ADMIN_FILES} ${JS_STOREFRONT_FILES})"

if [[ -n "$UNSTAGED_FILES" ]]
then
    echo "Error: There are staged files with unstaged changes. We cannot automatically fix and add those.

Please add or revert the following files:

$UNSTAGED_FILES
"
    exit 1
fi

if [[ -n "$PHP_FILES" ]]
then
    # fix code style and update the commit
    ./bin/fix-cs.sh
fi

if [[ -n "$JS_ADMIN_FILES" && -x ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/node_modules/.bin/eslint ]]
then
    ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/node_modules/.bin/eslint --config ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/.eslintrc.js --ext .js,.vue --fix ${JS_ADMIN_FILES}
fi

if [[ -n "$JS_STOREFRONT_FILES" && -x ../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint ]]
then
    ../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint --config ../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/.eslintrc.js --ext .js,.vue --fix ${JS_STOREFRONT_FILES}
fi

git add ${JS_ADMIN_FILES} ${JS_STOREFRONT_FILES} ${PHP_FILES}

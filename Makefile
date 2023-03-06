.DEFAULT_GOAL := help

## Variable definition
PLUGIN_ROOT=$(shell cd -P -- '$(shell dirname -- "$0")' && pwd -P)
PROJECT_ROOT=$(PLUGIN_ROOT)/../../..
ifneq ("$(wildcard $(PROJECT_ROOT)/platform)", "")
    PLATFORM_ROOT=$(PROJECT_ROOT)/platform
else
	PLATFORM_ROOT=$(PROJECT_ROOT)
endif

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

ecs-fix: ## Run easy coding standard on php
	@php $(PLATFORM_ROOT)/vendor/bin/ecs check --fix --config=$(PLATFORM_ROOT)/ecs.php src tests
	@php $(PLATFORM_ROOT)/vendor/bin/ecs check --fix src tests
.PHONY: ecs-fix

phpstan:
	@composer dump-autoload --dev
	@php $(PLUGIN_ROOT)/bin/phpstan-config-generator.php
	@php $(PLATFORM_ROOT)/vendor/bin/phpstan analyze --configuration $(PLUGIN_ROOT)/phpstan.neon
.PHONY: phpstan

phpunit:
	@composer dump-autoload --dev
	@touch $(PLUGIN_ROOT)/vendor/composer/InstalledVersions.php
	@$(PLATFORM_ROOT)/vendor/bin/phpunit $(test)
.PHONY: phpunit

phpunit-coverage:
	make phpunit test="--coverage-html coverage $(test)"
.PHONY: phpunit

administration-fix: ## Run eslint on the administration files
	@npm run lint-fix --prefix $(PLUGIN_ROOT)/src/Resources/app/administration
.PHONY: administration-fix

storefront-fix: ## Run eslint on the storefront files
	cd $(PLUGIN_ROOT)/src/Resources/app/storefront; \
	$(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint \
	    --config $(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/.eslintrc.js \
	    --fix ./src; \
	$(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/node_modules/.bin/stylelint \
		--config $(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/stylelint.config.js \
	    --fix ./src;
.PHONY: storefront-fix

administration-lint: ## Run eslint on the administration files
	@npm run lint --prefix $(PLUGIN_ROOT)/src/Resources/app/administration
.PHONY: administration-lint

storefront-lint: ## Run eslint on the storefront files
	cd $(PLUGIN_ROOT)/src/Resources/app/storefront; \
	$(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint \
	    --config $(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/.eslintrc.js \
	    ./src; \
	$(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/node_modules/.bin/stylelint \
		--config $(PLATFORM_ROOT)/src/Storefront/Resources/app/storefront/stylelint.config.js \
		./src;
.PHONY: storefront-lint

administration-ci: ## Run eslint on the administration files
	@npm run lint-ci --prefix $(PLUGIN_ROOT)/src/Resources/app/administration
.PHONY: administration-ci

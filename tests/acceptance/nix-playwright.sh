#!/usr/bin/env bash

version="$(jq -r '.dependencies["@playwright/test"]' package.json)"

PLAYWRIGHT_BROWSERS_PATH=$(nix build --print-out-paths --no-link "github:pietdevries94/playwright-web-flake/$version#playwright-driver.browsers") npx playwright "$@"

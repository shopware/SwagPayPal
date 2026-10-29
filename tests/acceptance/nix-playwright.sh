#!/usr/bin/env bash

version="$(jq -r '.packages["node_modules/playwright-core"].version' package-lock.json)"

PLAYWRIGHT_BROWSERS_PATH=$(nix build --print-out-paths --no-link "github:pietdevries94/playwright-web-flake/$version#playwright-driver.browsers") npx playwright "$@"

## Setup

Navigate to this directory if you haven't yet.

```
cd tests/acceptance
```

Install the project dependencies.

```
npm install
```

Install Playwright.

```
npx playwright install chromium
npx playwright install-deps
```

Make sure to add the required environment variables to your `.env` file in the acceptance test directory of the PayPal plugin (not the shopware root).

```
APP_URL="<shop base url>"
PAYPAL_CLIENT_ID="<...>"
PAYPAL_CLIENT_SECRET="<...>"
PAYPAL_MERCHANT_ID="<...>"

# optional with default dev setup
SHOPWARE_ACCESS_KEY_ID="<your-api-client-id>"
SHOPWARE_SECRET_ACCESS_KEY="<your-api-secret>"
```

To generate the access key you can use the following Symfony command:

```
bin/console integration:create AcceptanceTest --admin
```

## Running Tests

Navigate to `[paypal-repo]/tests/acceptance` and run:

```
npx playwright test
```

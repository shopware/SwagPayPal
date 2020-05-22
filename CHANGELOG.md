# REPLACE-GLOBAL-WITH-NEXT-VERSION
- PT-11717 - Adds PayPal cookies to the cookie manager

# 1.5.2
- PT-10502 - Canceling the payment on the PayPal site no longer leads to errors
- PT-11710 - Fix installation of plugin in environments where the default language is not de-DE or en-GB

# 1.5.1
- PT-10640 - Fix SalesChannel api routes
- PT-10897 - Locale code validation for Smart Payment Buttons and Express Checkout
- PT-11294 - Error handling for Smart Payment Buttons
- PT-11582 - Fix webhook registration
- PT-11637 - Improve capture and refund workflow

# 1.5.0
- NEXT-8322 - Shopware 6.2 compatibility
- PT-10654 - Activate and set PayPal as default for the selected Saleschannel in the settings module
- PT-11599 - Fixes a bug where PayPal Plus could not be configured individually per Saleschannel

# 1.4.0
- PT-11540 - Corrects remaining amount for multiple partial refunds
- PT-11541 - Improved behaviour of multiple partial refunds & captures
- PT-11606 - Shopware 6.2 compatibility

# 1.3.0
- PT-10448 - Adds onboarding to get API credentials with PayPal login in settings module
- PT-11292 - Adds possibility to enter separate credentials for sandbox mode in first run wizard
- PT-11498 - Adds PayPal Express Button to QuickView from CMS Extensions plugin
- PT-11550 - Fix usage of sandbox credentials after an update

# 1.2.0
- PT-11233 - Do not show PayPal Express Button on the product detail page, if the product is in clearance
- PT-11292 - Add possibility to enter separate credentials for sandbox mode

# 1.1.1
- PT-11443 - Solves an issue with the error handling with the paypal credentials
- PT-11475 - Improved processing of vouchers during checkout

# 1.1.0
- PT-11276 - Add banner for advertising installments

# 1.0.0
- PT-11181, PT-11275 - Add PayPal PLUS integration
- PT-11277 - The cart and order number submitting is now active by default

# 0.13.0
- Shopware 6.1 compatibility

# 0.12.0
- PT-10287 - Adds possibility to add the invoice number, description or reason while refunding an order
- PT-10705 - The PayPal settings are now in an own administration module
- PT-10771 - Improves displaying of Smart Payment Buttons
- PT-10775 - Improves order transaction state handling
- PT-10809 - Smart Payment Buttons can now be styled separately from Express Checkout Button
- PT-10821 - Fixes error on sale complete webhook execution
- NEXT-4282 - Reinstall of plugin does not duplicate configuration entries anymore

# 0.11.2
- PT-10733 - Fixes problem when automatically fetching API credentials in first run wizard

# 0.11.1
- PT-10755 - Fixes error while uninstalling and configuration error

# 0.11.0
- PT-10391 - Implements pay upon invoice
- PT-10695 - Adds error logging for API calls
- PT-10702 - Changes URL for Smart Payment Buttons javascript
- PT-10715 - Paypal is selected correctly again as payment method for Express Checkout
- PT-10723 - Smart Payment Buttons now no longer complete the order directly
- PT-10729 - The PayPal payment description now shows available payments with icons

# 0.10.1
- Improves link generation for Javascript API calls

# 0.10.0
- Adds onboarding for the first run wizard

# 0.9.0
- First version of the PayPal integrations for Shopware 6

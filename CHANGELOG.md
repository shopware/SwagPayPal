# 1.9.0
- PPI-1 - Fixes mobile layout of checkout finish for "Pay Upon Invoice"
- PPI-69 - The Express button is now hidden, when the PayPal payment method is disabled.
- PT-11048 - Introduce iZettle integration (Point of Sales)

# 1.8.4
- PPI-125 - Shopware 6.3.2.0 compatibility

# 1.8.3
- PPI-70 - Order number is now correctly submitted to PayPal for payments with Express Checkout, PLUS and Smart Payment Buttons

# 1.8.2
- PPI-46 - Fixes issue on refund without amount
- PPI-47, PPI-48 - Enhancement of the PayPal API elements

# 1.8.1
- PPI-32, PPI-35 - Improve extensibility for third party plugins
- PPI-36 - Add new PayPal API elements

# 1.8.0
- PT-11912 - Storefront snippets now get auto registered
- PT-11920 - Shopware 6.3 compatibility

# 1.7.3
- PT-11946 - Fix update with deactivated plugin
- PT-11949 - Fix setting paypal as the default payment method in the settings menu for all Sales Channels

# 1.7.2
- PT-10491 - Removed internally used custom field entity for transaction IDs
- PT-11627 - Order transactions now have the state "In Progress" when the payment process has been started
- PT-11680 - Removed unknown Sales Channel types from selection in settings
- PT-11681 - Fix order details header in Administration, if accessing payment details directly
- PT-11860 - Fix order confirmation email language with PayPal Plus
- PT-11888 - Minor performance improvement when creating a payment
- PT-11903 - Fix failed transaction status for user-canceled PayPal Plus payments
- PT-11928 - Limited length of text input fields in administration according to PayPal API

# 1.7.1
- PT-11884 - If PayPal is not available, Plus and Smart Payment Buttons are no longer loaded

# 1.7.0
- PT-11669 - Add compatibility with the after order payment process
- PT-11707 - Custom form parameter of the order confirm page are no longer ignored
- PT-11748 - Fix redirect URL for PayPal Plus and Express Checkout. Changed webhook URL to be independent of a storefront
- PT-11773 - Fix buying of Custom Products with PayPal
- PT-11813 - Error handling for Express Checkout button
- PT-11858 - Improved handling of multiple transactions per order
- PT-11869 - Improved handling of payments which were cancelled by customers

# 1.6.0
- PT-11519 - Registers webhooks with HTTPS
- PT-11593 - Adds hint for "Payment acquisition" option to clarify usage with PayPal PLUS
- PT-11704 - Fix displaying of Express Checkout Button on paginated product listing pages
- PT-11706 - Country states are now saved on Express Checkout
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
